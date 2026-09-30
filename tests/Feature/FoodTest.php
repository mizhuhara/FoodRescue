<?php

namespace Tests\Feature;

use App\Enums\FoodStatus;
use App\Models\Category;
use App\Models\Food;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FoodTest extends TestCase
{
    use RefreshDatabase;

    private function approvedPartner(): Partner
    {
        return Partner::factory()->approved()->create([
            'user_id' => User::factory()->partner()->create()->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Chicken Rice',
            'description' => 'Surplus chicken rice',
            'original_price' => 25000,
            'rescue_price' => 12000,
            'stock' => 5,
            'pickup_start' => '18:00',
            'pickup_end' => '20:00',
        ], $overrides);
    }

    public function test_public_can_list_available_foods(): void
    {
        $food = Food::factory()->create(['status' => FoodStatus::Available, 'stock' => 3]);
        Food::factory()->draft()->create();
        Food::factory()->soldOut()->create();

        $this->getJson('/api/v1/foods')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $food->id)
            ->assertJsonPath('data.0.discount_percent', $food->discount_percent)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_public_can_view_food_detail(): void
    {
        $food = Food::factory()->create();

        $this->getJson("/api/v1/foods/{$food->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $food->id)
            ->assertJsonPath('data.partner.business_name', $food->partner->business_name)
            ->assertJsonPath('data.category.id', $food->category_id);
    }

    public function test_food_list_can_be_searched(): void
    {
        $match = Food::factory()->create(['name' => 'Nasi Ayam Goreng']);
        Food::factory()->create(['name' => 'Sayur Lodeh']);

        $this->getJson('/api/v1/foods?search=Nasi')
            ->assertOk()
            ->assertJsonPath('data.0.id', $match->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_approved_partner_can_create_food(): void
    {
        $partner = $this->approvedPartner();

        $response = $this->actingAs($partner->user, 'sanctum')
            ->postJson('/api/v1/partner/foods', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'AVAILABLE')
            ->assertJsonPath('data.discount_percent', 52);

        $this->assertDatabaseHas('foods', [
            'partner_id' => $partner->id,
            'rescue_price' => 12000,
        ]);
    }

    public function test_approved_partner_can_upload_food_image(): void
    {
        Storage::fake('public');
        $partner = $this->approvedPartner();

        $this->actingAs($partner->user, 'sanctum')
            ->postJson('/api/v1/partner/foods', $this->payload([
                'image' => UploadedFile::fake()->image('food.jpg'),
            ]))
            ->assertCreated()
            ->assertJsonPath('data.image', fn ($path) => is_string($path) && str_starts_with($path, 'foods/'));

        Storage::disk('public')->assertExists(Food::first()->image);
    }

    public function test_pending_partner_cannot_create_food(): void
    {
        $partner = Partner::factory()->create();

        $this->actingAs($partner->user, 'sanctum')
            ->postJson('/api/v1/partner/foods', $this->payload())
            ->assertForbidden()
            ->assertJsonPath('message', 'Only approved partners can publish food.');
    }

    public function test_customer_cannot_create_food(): void
    {
        $this->actingAs(User::factory()->customer()->create(), 'sanctum')
            ->postJson('/api/v1/partner/foods', $this->payload())
            ->assertForbidden();
    }

    public function test_rescue_price_must_be_lower_than_original_price(): void
    {
        $partner = $this->approvedPartner();

        $this->actingAs($partner->user, 'sanctum')
            ->postJson('/api/v1/partner/foods', $this->payload(['rescue_price' => 30000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rescue_price']);
    }

    public function test_pickup_end_must_be_after_pickup_start(): void
    {
        $partner = $this->approvedPartner();

        $this->actingAs($partner->user, 'sanctum')
            ->postJson('/api/v1/partner/foods', $this->payload([
                'pickup_start' => '20:00',
                'pickup_end' => '18:00',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['pickup_end']);
    }

    public function test_stock_must_be_greater_than_zero(): void
    {
        $partner = $this->approvedPartner();

        $this->actingAs($partner->user, 'sanctum')
            ->postJson('/api/v1/partner/foods', $this->payload(['stock' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stock']);
    }

    public function test_partner_can_update_own_food(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->create(['stock' => 1]);

        $this->actingAs($partner->user, 'sanctum')
            ->putJson("/api/v1/partner/foods/{$food->id}", ['stock' => 9, 'name' => 'Nasi Ayam Bakar'])
            ->assertOk()
            ->assertJsonPath('data.stock', 9)
            ->assertJsonPath('data.name', 'Nasi Ayam Bakar');

        $this->assertSame(9, $food->fresh()->stock);
    }

    public function test_partner_cannot_update_another_partners_food(): void
    {
        $other = Food::factory()->create();

        $this->actingAs($this->approvedPartner()->user, 'sanctum')
            ->putJson("/api/v1/partner/foods/{$other->id}", ['stock' => 99])
            ->assertForbidden();
    }

    public function test_partner_delete_soft_deletes_food(): void
    {
        $partner = $this->approvedPartner();
        $food = Food::factory()->for($partner, 'partner')->create();

        $this->actingAs($partner->user, 'sanctum')
            ->deleteJson("/api/v1/partner/foods/{$food->id}")
            ->assertOk();

        $this->assertSoftDeleted($food);
    }

    public function test_partner_only_sees_own_foods(): void
    {
        $partner = $this->approvedPartner();
        Food::factory()->for($partner, 'partner')->create();
        Food::factory()->create();

        $this->actingAs($partner->user, 'sanctum')
            ->getJson('/api/v1/partner/foods')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.partner_id', $partner->id);
    }

    public function test_categories_are_listed(): void
    {
        Category::factory()->create(['name' => 'Rice']);
        Category::factory()->create(['name' => 'Bakery']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_foods_can_be_sorted_by_distance_when_user_has_location(): void
    {
        $user = User::factory()->customer()->create([
            'latitude' => -5.147665,
            'longitude' => 119.432724,
        ]);

        $far = Partner::factory()->approved()->create([
            'latitude' => -5.500000,
            'longitude' => 119.500000,
        ]);
        $near = Partner::factory()->approved()->create([
            'latitude' => -5.150000,
            'longitude' => 119.435000,
        ]);

        $farFood = Food::factory()->create(['partner_id' => $far->id, 'pickup_end' => '23:59']);
        $nearFood = Food::factory()->create(['partner_id' => $near->id, 'pickup_end' => '23:59']);

        $response = $this->actingAs($user)->getJson('/api/v1/foods?sort=nearest');

        $response->assertOk();
        $this->assertEquals(
            [$nearFood->id, $farFood->id],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_foods_without_user_location_fall_back_to_pickup_end_sort(): void
    {
        $user = User::factory()->customer()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        Food::factory()->count(2)->create(['pickup_end' => '23:59']);

        $this->actingAs($user)
            ->getJson('/api/v1/foods?sort=nearest')
            ->assertOk();
    }
}
