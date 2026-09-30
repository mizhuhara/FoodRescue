<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Partner;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_impact(): void
    {
        $this->getJson('/api/v1/impact')->assertUnauthorized();
    }

    public function test_customer_impact_is_zero_for_new_user(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/impact')
            ->assertOk()
            ->assertJsonPath('data.food_rescued', 0)
            ->assertJsonPath('data.orders_completed', 0)
            ->assertJsonPath('data.total_spent', 0);
    }

    public function test_customer_impact_counts_only_completed_orders(): void
    {
        $user = User::factory()->customer()->create();
        Order::factory()->completed()->count(2)->create(['user_id' => $user->id, 'total' => 25000]);
        Order::factory()->pending()->create(['user_id' => $user->id, 'total' => 99000]);

        $response = $this->actingAs($user)->getJson('/api/v1/impact');

        $response->assertOk()
            ->assertJsonPath('data.orders_completed', 2)
            ->assertJsonPath('data.total_spent', 50000);
        $this->assertGreaterThanOrEqual(2, $response->json('data.food_rescued'));
    }

    public function test_customer_impact_includes_review_rating(): void
    {
        $user = User::factory()->customer()->create();
        $orders = Order::factory()->completed()->count(2)->create(['user_id' => $user->id]);

        Review::create([
            'order_id' => $orders[0]->id,
            'user_id' => $user->id,
            'food_id' => $orders[0]->items()->first()->food_id,
            'rating' => 5,
        ]);
        Review::create([
            'order_id' => $orders[1]->id,
            'user_id' => $user->id,
            'food_id' => $orders[1]->items()->first()->food_id,
            'rating' => 3,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/impact')
            ->assertOk()
            ->assertJsonPath('data.avg_rating', 4);
    }

    public function test_customer_cannot_view_partner_impact(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->getJson('/api/v1/partner/impact')->assertForbidden();
    }

    public function test_partner_impact_aggregates_completed_orders(): void
    {
        $partnerUser = User::factory()->partner()->create();
        $partner = Partner::factory()->approved()->create(['user_id' => $partnerUser->id]);

        Order::factory()->completed()->count(3)->create([
            'partner_id' => $partner->id,
            'total' => 15000,
        ]);

        $response = $this->actingAs($partnerUser)->getJson('/api/v1/partner/impact');

        $response->assertOk()
            ->assertJsonPath('data.orders_completed', 3)
            ->assertJsonPath('data.revenue', 45000);
        $this->assertGreaterThanOrEqual(3, $response->json('data.food_rescued'));
    }

    public function test_partner_without_profile_impact_returns_404(): void
    {
        $partnerUser = User::factory()->partner()->create();

        $this->actingAs($partnerUser)
            ->getJson('/api/v1/partner/impact')
            ->assertStatus(404);
    }
}
