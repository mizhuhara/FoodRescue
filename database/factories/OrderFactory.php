<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'partner_id' => Partner::factory()->approved(),
            'order_number' => 'FR-'.date('Ymd').'-'.Str::upper(Str::random(6)),
            'subtotal' => fake()->numberBetween(10000, 100000),
            'total' => fake()->numberBetween(10000, 100000),
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'pickup_code' => null,
            'expires_at' => now()->addHours(2),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            if ($order->items()->count() === 0) {
                $food = Food::factory()->create(['partner_id' => $order->partner_id]);
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'food_id' => $food->id,
                ]);
            }
        });
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Pending]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Confirmed]);
    }

    public function readyForPickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::ReadyForPickup,
            'pickup_code' => Str::upper(Str::random(8)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Completed,
            'pickup_completed_at' => now(),
        ]);
    }
}
