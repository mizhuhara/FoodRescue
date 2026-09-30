<?php

namespace App\Services;

use App\Enums\FoodStatus;
use App\Enums\OrderStatus;
use App\Enums\PartnerStatus;
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

    private function pickupDeadline(Food $food): Carbon
    {
        return today()->setTimeFromTimeString($food->pickup_end);
    }

    private function generateOrderNumber(): string
    {
        return 'FR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
