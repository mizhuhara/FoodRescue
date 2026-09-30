<?php

namespace Tests\Feature;

use App\Enums\PartnerStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_view_own_profile(): void
    {
        $user = User::factory()->partner()->create();
        $partner = Partner::factory()->for($user)->create([
            'business_name' => 'Test Restaurant',
            'status' => PartnerStatus::Pending,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/partner/profile')
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Test Restaurant')
            ->assertJsonPath('data.id', $partner->id);
    }

    public function test_customer_cannot_access_partner_profile_endpoint(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/partner/profile')
            ->assertForbidden();
    }

    public function test_partner_can_create_profile(): void
    {
        $user = User::factory()->partner()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/partner/profile', [
                'business_name' => 'New Restaurant',
                'description' => 'Great food here',
                'phone' => '081234567890',
                'address' => 'Jl. Main St, Denpasar',
                'latitude' => -8.6705,
                'longitude' => 115.2126,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.business_name', 'New Restaurant')
            ->assertJsonPath('data.id', fn ($id) => $id > 0);

        $this->assertDatabaseHas('partners', [
            'user_id' => $user->id,
            'business_name' => 'New Restaurant',
        ]);
    }

    public function test_partner_can_update_profile(): void
    {
        $user = User::factory()->partner()->create();
        $partner = Partner::factory()->for($user)->create([
            'business_name' => 'Old Name',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/partner/profile', [
                'business_name' => 'New Name',
                'description' => 'Updated desc',
                'phone' => '082345678901',
                'address' => 'New Address',
                'latitude' => -8.67,
                'longitude' => 115.21,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.business_name', 'New Name');

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'business_name' => 'New Name',
        ]);
    }

    public function test_partner_dashboard_shows_stats(): void
    {
        $user = User::factory()->partner()->create();
        $partner = Partner::factory()->for($user)->approved()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/partner/dashboard')
            ->assertOk()
            ->assertJsonPath('data.orders_today', 0)
            ->assertJsonPath('data.revenue_today', 0)
            ->assertJsonPath('data.food_rescued', 0)
            ->assertJsonPath('data.active_foods', 0);
    }

    public function test_profile_requires_valid_coordinates(): void
    {
        $user = User::factory()->partner()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/partner/profile', [
                'business_name' => 'Test',
                'phone' => '081234567890',
                'address' => 'Addr',
                'latitude' => 999,
                'longitude' => 115.21,
            ])
            ->assertStatus(422);
    }
}
