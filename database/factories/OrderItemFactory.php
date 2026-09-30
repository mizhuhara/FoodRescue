<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(5000, 50000);
        $quantity = fake()->numberBetween(1, 10);

        return [
            'order_id' => fn () => Order::factory(),
            'food_id' => fn () => Food::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
        ];
    }
}
