<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->completed(),
            'user_id' => fn (array $attributes) => Order::find($attributes['order_id'])->user_id,
            'food_id' => Food::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
            'image' => null,
        ];
    }
}
