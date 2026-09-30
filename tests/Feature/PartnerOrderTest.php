<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Food;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerOrderTest extends TestCase
{
    use RefreshDatabase;

    private function approvedPartner(): Partner
    {
        return Partner::factory()->approved()->create([
            'user_id' => User::factory()->partner()->create()->id,
        ]);
    }

    public function test_partner_can_view_own_orders(): void
    {
        $partner = $this->approvedPartner();
        $customer = User::factory()->customer()->create();

        $order1 = Order::factory()->for($customer, 'user')->for($partner, 'partner')->create();
        $order2 = Order::factory()->for($partner, 'partner')->create();

        $this->actingAs($partner->user, 'sanctum')
            ->getJson('/api/v1/partner/orders')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_partner_cannot_view_other_partners_orders(): void
    {
        $partner1 = $this->approvedPartner();
        $partner2 = $this->approvedPartner();

        Order::factory()->for($partner2, 'partner')->create();

        $this->actingAs($partner1->user, 'sanctum')
            ->getJson('/api/v1/partner/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_partner_can_transition_pending_to_confirmed(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->pending()->create();

        $this->actingAs($partner->user, 'sanctum')
            ->patchJson("/api/v1/partner/orders/{$order->id}/status", [
                'status' => 'CONFIRMED',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'CONFIRMED');

        $this->assertSame('CONFIRMED', $order->fresh()->status->value);
    }

    public function test_partner_can_transition_confirmed_to_preparing(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->confirmed()->create();

        $this->actingAs($partner->user, 'sanctum')
            ->patchJson("/api/v1/partner/orders/{$order->id}/status", [
                'status' => 'PREPARING',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'PREPARING');
    }

    public function test_partner_can_transition_preparing_to_ready_for_pickup(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->create([
            'status' => OrderStatus::Preparing,
        ]);

        $this->actingAs($partner->user, 'sanctum')
            ->patchJson("/api/v1/partner/orders/{$order->id}/status", [
                'status' => 'READY_FOR_PICKUP',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'READY_FOR_PICKUP')
            ->assertJsonPath('data.pickup_code', fn ($code) => is_string($code) && strlen($code) > 0);

        $this->assertNotNull($order->fresh()->pickup_code);
    }

    public function test_partner_cannot_transition_invalid_state(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->pending()->create();

        $this->actingAs($partner->user, 'sanctum')
            ->patchJson("/api/v1/partner/orders/{$order->id}/status", [
                'status' => 'PREPARING',
            ])
            ->assertStatus(422);
    }

    public function test_partner_cannot_set_completed_or_expired_manually(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->pending()->create();

        $this->actingAs($partner->user, 'sanctum')
            ->patchJson("/api/v1/partner/orders/{$order->id}/status", [
                'status' => 'COMPLETED',
            ])
            ->assertStatus(422);

        $this->actingAs($partner->user, 'sanctum')
            ->patchJson("/api/v1/partner/orders/{$order->id}/status", [
                'status' => 'EXPIRED',
            ])
            ->assertStatus(422);
    }

    public function test_partner_can_verify_pickup_with_valid_qr(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->readyForPickup()->create();
        $pickupCode = $order->pickup_code;

        $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/orders/{$order->id}/pickup", [
                'pickup_code' => $pickupCode,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED')
            ->assertJsonPath('data.pickup_completed_at', fn ($t) => $t !== null)
            ->assertJsonPath('data.payment_status', 'PAID');

        $this->assertSame('COMPLETED', $order->fresh()->status->value);
        $this->assertNotNull($order->fresh()->pickup_completed_at);
    }

    public function test_partner_cannot_verify_pickup_with_wrong_qr(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->readyForPickup()->create();

        $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/orders/{$order->id}/pickup", [
                'pickup_code' => 'WRONGCODE',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid pickup code.');

        $this->assertSame('READY_FOR_PICKUP', $order->fresh()->status->value);
    }

    public function test_pickup_qr_single_use_prevents_reuse(): void
    {
        $partner = $this->approvedPartner();
        $order = Order::factory()->for($partner, 'partner')->readyForPickup()->create();
        $pickupCode = $order->pickup_code;

        $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/orders/{$order->id}/pickup", [
                'pickup_code' => $pickupCode,
            ])
            ->assertOk();

        $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/orders/{$order->id}/pickup", [
                'pickup_code' => $pickupCode,
            ])
            ->assertStatus(422);
    }

    public function test_partner_cannot_verify_pickup_for_wrong_order(): void
    {
        $partner1 = $this->approvedPartner();
        $partner2 = $this->approvedPartner();

        $order = Order::factory()->for($partner1, 'partner')->readyForPickup()->create();

        $this->actingAs($partner2->user, 'sanctum')
            ->postJson("/api/v1/partner/orders/{$order->id}/pickup", [
                'pickup_code' => $order->pickup_code,
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_access_partner_orders_endpoint(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/partner/orders')
            ->assertForbidden();
    }

    public function test_partner_can_cancel_pending_order_restocks_food(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->available()->create(['stock' => 5]);
        $customer = User::factory()->customer()->create();

        $order = Order::factory()->for($customer, 'user')->for($partner, 'partner')->pending()->create();
        $order->items()->create([
            'food_id' => $food->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        $food->update(['stock' => 3]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk();

        $this->assertSame(5, $food->fresh()->stock);
        $this->assertSame('AVAILABLE', $food->fresh()->status->value);
    }
}
