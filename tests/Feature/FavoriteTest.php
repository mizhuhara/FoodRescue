<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_favorites(): void
    {
        $this->getJson('/api/v1/favorites')->assertUnauthorized();
    }

    public function test_customer_can_add_food_to_favorites(): void
    {
        $user = User::factory()->customer()->create();
        $food = Food::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/foods/{$food->id}/favorite")
            ->assertCreated()
            ->assertJsonPath('data.id', $food->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'food_id' => $food->id,
        ]);
    }

    public function test_adding_same_food_twice_is_idempotent(): void
    {
        $user = User::factory()->customer()->create();
        $food = Food::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/foods/{$food->id}/favorite")->assertCreated();
        $this->actingAs($user)->postJson("/api/v1/foods/{$food->id}/favorite")->assertOk();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_customer_can_remove_food_from_favorites(): void
    {
        $user = User::factory()->customer()->create();
        $food = Food::factory()->create();
        Favorite::create(['user_id' => $user->id, 'food_id' => $food->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/foods/{$food->id}/favorite")
            ->assertOk();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'food_id' => $food->id,
        ]);
    }

    public function test_removing_favorite_that_does_not_exist_still_succeeds(): void
    {
        $user = User::factory()->customer()->create();
        $food = Food::factory()->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/foods/{$food->id}/favorite")
            ->assertOk();
    }

    public function test_customer_only_sees_own_favorites(): void
    {
        $user = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $myFood = Food::factory()->create();
        $theirFood = Food::factory()->create();

        Favorite::create(['user_id' => $user->id, 'food_id' => $myFood->id]);
        Favorite::create(['user_id' => $other->id, 'food_id' => $theirFood->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/favorites');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals($myFood->id, $response->json('data.0.id'));
    }

    public function test_favorites_are_ordered_newest_first(): void
    {
        $user = User::factory()->customer()->create();
        $first = Food::factory()->create();
        $second = Food::factory()->create();

        Favorite::create(['user_id' => $user->id, 'food_id' => $first->id])
            ->forceFill(['created_at' => now()->subDay()])
            ->save();
        Favorite::create(['user_id' => $user->id, 'food_id' => $second->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/favorites');

        $this->assertEquals($second->id, $response->json('data.0.id'));
    }
}
