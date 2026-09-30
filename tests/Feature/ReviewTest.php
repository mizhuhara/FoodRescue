<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Food;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_submit_review(): void
    {
        $order = Order::factory()->completed()->create();

        $this->postJson("/api/v1/orders/{$order->id}/review", [
            'food_id' => $order->items()->first()->food_id,
            'rating' => 5,
        ])->assertUnauthorized();
    }

    public function test_customer_can_review_completed_order(): void
    {
        $order = Order::factory()->completed()->create();
        $foodId = $order->items()->first()->food_id;

        $response = $this->actingAs($order->user)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $foodId,
                'rating' => 5,
                'comment' => 'Amazing food!',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Amazing food!');

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating' => 5,
        ]);
    }

    public function test_cannot_review_pending_order(): void
    {
        $order = Order::factory()->pending()->create();

        $this->actingAs($order->user)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $order->items()->first()->food_id,
                'rating' => 4,
            ])
            ->assertStatus(422);
    }

    public function test_cannot_review_same_order_twice(): void
    {
        $order = Order::factory()->completed()->create();
        $foodId = $order->items()->first()->food_id;

        $this->actingAs($order->user)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $foodId,
                'rating' => 5,
            ])
            ->assertCreated();

        $this->actingAs($order->user)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $foodId,
                'rating' => 3,
            ])
            ->assertStatus(422);
    }

    public function test_cannot_review_other_users_order(): void
    {
        $order = Order::factory()->completed()->create();
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $order->items()->first()->food_id,
                'rating' => 1,
            ])
            ->assertStatus(403);
    }

    public function test_cannot_review_food_not_in_order(): void
    {
        $order = Order::factory()->completed()->create();
        $otherFood = Food::factory()->create();

        $this->actingAs($order->user)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $otherFood->id,
                'rating' => 5,
            ])
            ->assertStatus(422);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $order = Order::factory()->completed()->create();

        $this->actingAs($order->user)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $order->items()->first()->food_id,
                'rating' => 9,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating');
    }

    public function test_food_reviews_are_listed(): void
    {
        $food = Food::factory()->create();
        Review::factory()->count(3)->create(['food_id' => $food->id]);

        $this->getJson("/api/v1/foods/{$food->id}/reviews")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_review_is_rejected_when_order_not_completed_even_if_authorized(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled]);

        $this->actingAs($order->user)
            ->postJson("/api/v1/orders/{$order->id}/review", [
                'food_id' => $order->items()->first()->food_id,
                'rating' => 5,
            ])
            ->assertStatus(422);
    }
}
