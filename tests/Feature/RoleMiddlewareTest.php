<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'role:PARTNER'])->get('/test-partner-only', function () {
            return response()->json(['success' => true]);
        });

        Route::middleware(['auth:sanctum', 'role:ADMIN'])->get('/test-admin-only', function () {
            return response()->json(['success' => true]);
        });
    }

    public function test_customer_cannot_access_partner_route(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/test-partner-only')
            ->assertStatus(403);
    }

    public function test_partner_can_access_partner_route(): void
    {
        $partner = User::factory()->partner()->create();

        $this->actingAs($partner, 'sanctum')
            ->getJson('/test-partner-only')
            ->assertStatus(200);
    }

    public function test_customer_cannot_access_admin_route(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/test-admin-only')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/test-admin-only')
            ->assertStatus(200);
    }
}
