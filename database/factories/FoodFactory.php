<?php

namespace Database\Factories;

use App\Enums\FoodStatus;
use App\Models\Category;
use App\Models\Food;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalPrice = fake()->numberBetween(10000, 100000);
        $rescuePrice = fake()->numberBetween(5000, $originalPrice - 1000);

        $name = fake()->words(2, true);

        return [
            'partner_id' => Partner::factory(),
            'category_id' => fn () => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'image' => null,
            'original_price' => $originalPrice,
            'rescue_price' => $rescuePrice,
            'stock' => fake()->numberBetween(1, 50),
            'pickup_start' => '18:00',
            'pickup_end' => '20:00',
            'status' => FoodStatus::Available,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => FoodStatus::Draft]);
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => ['status' => FoodStatus::Available]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => ['stock' => 0, 'status' => FoodStatus::SoldOut]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['status' => FoodStatus::Expired]);
    }
}
