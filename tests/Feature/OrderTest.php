<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function approvedPartner(): Partner
    {
        return Partner::factory()->approved()->create([
            'user_id' => User::factory()->partner()->create()->id,
        ]);
    }

    public function test_customer_can_create_order(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->available()->create([
            'stock' => 10,
            'rescue_price' => 10000,
        ]);

        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['food_id' => $food->id, 'quantity' => 2],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.unit_price', 10000)
            ->assertJsonPath('data.total', 20000);

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'partner_id' => $partner->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'food_id' => $food->id,
            'quantity' => 2,
        ]);

        $this->assertSame(8, $food->fresh()->stock);
    }

    public function test_order_creation_reduces_stock_atomically(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->available()->create(['stock' => 2]);

        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [['food_id' => $food->id, 'quantity' => 2]],
            ]);

        $response->assertCreated();
        $this->assertSame(0, $food->fresh()->stock);
        $this->assertSame('SOLD_OUT', $food->fresh()->status->value);
    }

    public function test_order_cannot_be_created_with_insufficient_stock(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->available()->create(['stock' => 1]);

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [['food_id' => $food->id, 'quantity' => 5]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Insufficient stock for '.$food->name.'.');
    }

    public function test_order_cannot_be_created_from_unapproved_partner(): void
    {
        $partner = Partner::factory()->create();
        $food = Food::factory()->for($partner, 'partner')->available()->create();

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [['food_id' => $food->id, 'quantity' => 1]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Partner '.$partner->business_name.' is not approved.');
    }

    public function test_order_cannot_be_created_with_expired_food(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->expired()->create();

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [['food_id' => $food->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);
    }

    public function test_order_cannot_be_created_with_mixed_partners(): void
    {
        $partner1 = $this->approvedPartner();
        $partner2 = $this->approvedPartner();
        $food1 = Food::factory()->for($partner1, 'partner')->available()->create();
        $food2 = Food::factory()->for($partner2, 'partner')->available()->create();

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['food_id' => $food1->id, 'quantity' => 1],
                    ['food_id' => $food2->id, 'quantity' => 1],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'All items must be from the same partner.');
    }

    public function test_customer_can_view_own_orders(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer, 'user')->create();
        Order::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $order->id);
    }

    public function test_customer_can_view_order_detail(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer, 'user')->has(
            OrderItem::factory()->count(2),
            'items'
        )->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();
        $order = Order::factory()->for($customer1, 'user')->create();

        $this->actingAs($customer2, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertForbidden();
    }

    public function test_customer_can_cancel_pending_order(): void
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
            ->assertOk()
            ->assertJsonPath('data.status', 'CANCELLED');

        $this->assertSame(5, $food->fresh()->stock);
        $this->assertSame('AVAILABLE', $food->fresh()->status->value);
    }

    public function test_customer_cannot_cancel_non_pending_order(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer, 'user')->confirmed()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Only pending orders can be cancelled.');
    }

    public function test_order_uses_server_side_pricing(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->available()->create([
            'rescue_price' => 10000,
        ]);

        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['food_id' => $food->id, 'quantity' => 1, 'price' => 999],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.unit_price', 10000)
            ->assertJsonPath('data.total', 10000);
    }
}
