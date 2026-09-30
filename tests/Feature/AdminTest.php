<?php

namespace Tests\Feature;

use App\Enums\PartnerStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/admin/partners/pending')
            ->assertForbidden();
    }

    public function test_admin_can_list_pending_partners(): void
    {
        $admin = User::factory()->admin()->create();
        Partner::factory()->count(2)->create(['status' => PartnerStatus::Pending]);
        Partner::factory()->create(['status' => PartnerStatus::Approved]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/partners/pending');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_approve_pending_partner(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = Partner::factory()->create(['status' => PartnerStatus::Pending]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/admin/partners/{$partner->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'APPROVED');

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'status' => 'APPROVED',
        ]);
        $this->assertNotNull($partner->fresh()->verified_at);
    }

    public function test_admin_cannot_approve_non_pending_partner(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = Partner::factory()->create(['status' => PartnerStatus::Approved]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/partners/{$partner->id}/approve")
            ->assertStatus(422);
    }

    public function test_admin_can_reject_pending_partner(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = Partner::factory()->create(['status' => PartnerStatus::Pending]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/admin/partners/{$partner->id}/reject", [
                'reason' => 'Incomplete documentation',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'REJECTED');

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'status' => 'REJECTED',
        ]);
    }

    public function test_admin_can_suspend_partner(): void
    {
        $admin = User::factory()->admin()->create();
        $partner = Partner::factory()->create(['status' => PartnerStatus::Approved]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/v1/admin/partners/{$partner->id}/suspend", [
                'reason' => 'Repeated violations',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'SUSPENDED');

        $this->assertDatabaseHas('partners', [
            'id' => $partner->id,
            'status' => 'SUSPENDED',
        ]);
    }
}
