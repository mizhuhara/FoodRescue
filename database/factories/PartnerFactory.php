<?php

namespace Database\Factories;

use App\Enums\PartnerStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->partner(),
            'business_name' => fake()->company(),
            'description' => fake()->sentence(),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'latitude' => -8.6705 + fake()->randomFloat(6, -0.05, 0.05),
            'longitude' => 115.2126 + fake()->randomFloat(6, -0.05, 0.05),
            'logo' => null,
            'status' => PartnerStatus::Pending,
            'verified_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PartnerStatus::Approved,
            'verified_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PartnerStatus::Suspended]);
    }
}
