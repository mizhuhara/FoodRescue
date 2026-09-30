<?php

namespace App\Services;

use App\Enums\FoodStatus;
use App\Enums\OrderStatus;
use App\Enums\PartnerStatus;
use App\Enums\PaymentStatus;
use App\Models\Food;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    /**
     * @param  array<int, array{food_id: int, quantity: int}>  $items
     */
    public function create(User $customer, array $items): Order
    {
        return DB::transaction(function () use ($customer, $items) {
            $foods = Food::whereIn('id', array_column($items, 'food_id'))
                ->with('partner')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $partnerId = null;
            $subtotal = 0;
            $expiresAt = null;

            foreach ($items as $item) {
                $food = $foods->get($item['food_id']);

                if (! $food) {
                    throw new InvalidArgumentException("Food {$item['food_id']} not found.");
                }

                if ($food->status !== FoodStatus::Available) {
                    throw new InvalidArgumentException("Food {$food->name} is not available.");
                }

                if ($food->stock < $item['quantity']) {
                    throw new InvalidArgumentException("Insufficient stock for {$food->name}.");
                }

                if ($food->partner->status !== PartnerStatus::Approved) {
                    throw new InvalidArgumentException("Partner {$food->partner->business_name} is not approved.");
                }

                $deadline = $this->pickupDeadline($food);

                if ($deadline->isPast()) {
                    throw new InvalidArgumentException("Pickup window for {$food->name} has ended.");
                }

                if ($partnerId !== null && $partnerId !== $food->partner_id) {
                    throw new InvalidArgumentException('All items must be from the same partner.');
                }

                $partnerId = $food->partner_id;
                $subtotal += $food->rescue_price * $item['quantity'];
                $expiresAt = $deadline;
            }

            $order = Order::create([
                'user_id' => $customer->id,
                'partner_id' => $partnerId,
                'order_number' => $this->generateOrderNumber(),
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'status' => OrderStatus::Pending,
                'expires_at' => $expiresAt,
            ]);

            foreach ($items as $item) {
                /** @var Food $food */
                $food = $foods->get($item['food_id']);

                $order->items()->create([
                    'food_id' => $food->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $food->rescue_price,
                    'subtotal' => $food->rescue_price * $item['quantity'],
                ]);

                $food->decrement('stock', $item['quantity']);

                if ($food->stock === 0) {
                    $food->update(['status' => FoodStatus::SoldOut]);
                }
            }

            return $order;
        });
    }

    /**
     * Move an order to a new status, rejecting transitions the state machine forbids.
     *
     * @throws InvalidArgumentException
     */
    public function updateStatus(Order $order, OrderStatus $target): Order
    {
        return DB::transaction(function () use ($order, $target) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($target)) {
                throw new InvalidArgumentException(
                    "Cannot change order status from {$locked->status->value} to {$target->value}."
                );
            }

            $locked->status = $target;

            if ($target === OrderStatus::ReadyForPickup) {
                $locked->pickup_code = $this->generatePickupCode();
            }

            if (in_array($target, [OrderStatus::Cancelled, OrderStatus::Expired], true)) {
                $this->restockItems($locked);
            }

            $locked->save();

            return $locked;
        });
    }

    /**
     * Verify a scanned QR code and complete the order.
     *
     * Single-use is enforced by re-reading the order under a row lock inside a
     * transaction, so two simultaneous scans of the same code cannot both succeed.
     *
     * @throws InvalidArgumentException
     */
    public function verifyPickup(Order $order, string $pickupCode): Order
    {
        return DB::transaction(function () use ($order, $pickupCode) {
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::ReadyForPickup) {
                throw new InvalidArgumentException('Only orders ready for pickup can be verified.');
            }

            if ($locked->expires_at?->isPast()) {
                throw new InvalidArgumentException('This order has expired and can no longer be picked up.');
            }

            if (! hash_equals((string) $locked->pickup_code, $pickupCode)) {
                throw new InvalidArgumentException('Invalid pickup code.');
            }

            $locked->status = OrderStatus::Completed;
            $locked->payment_status = PaymentStatus::Paid;
            $locked->pickup_completed_at = now();
            $locked->save();

            return $locked;
        });
    }

    /**
     * Return reserved stock to the catalogue when an order is cancelled or expires.
     *
     * Food whose pickup window has already closed stays expired instead of going
     * back on sale, so an expired order can never resurrect unsellable stock.
     */
    private function restockItems(Order $order): void
    {
        foreach ($order->items as $item) {
            $food = $item->food;

            if (! $food || $food->trashed()) {
                continue;
            }

            $food->increment('stock', $item->quantity);

            if ($this->pickupDeadline($food)->isPast()) {
                $food->update(['status' => FoodStatus::Expired]);
            } else {
                $food->update(['status' => FoodStatus::Available]);
            }
        }
    }

    private function pickupDeadline(Food $food): Carbon
    {
        return today()->setTimeFromTimeString($food->pickup_end);
    }

    private function generateOrderNumber(): string
    {
        return 'FR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    /**
     * Random, unique-per-order pickup code embedded in the customer's QR.
     *
     * ponytail: 10-char random token relies on collision probability rather than a
     * retry loop. Swap for a retry-on-unique-violation generator once order volume
     * makes collisions observable.
     */
    private function generatePickupCode(): string
    {
        return Str::upper(Str::random(10));
    }
}
