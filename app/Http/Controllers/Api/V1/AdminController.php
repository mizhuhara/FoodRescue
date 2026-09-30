<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PartnerStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PartnerResource;
use App\Models\Partner;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function pendingPartners(): JsonResponse
    {
        $partners = Partner::where('status', PartnerStatus::Pending)
            ->with('user')
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return PartnerResource::collection($partners)
            ->additional(['success' => true, 'message' => 'Pending partners retrieved.'])
            ->response();
    }

    public function approvePartner(Partner $partner): JsonResponse
    {
        if ($partner->status !== PartnerStatus::Pending) {
            return ApiResponse::error('Partner is not pending.', 422);
        }

        $partner->update([
            'status' => PartnerStatus::Approved,
            'verified_at' => now(),
        ]);

        return ApiResponse::success(
            new PartnerResource($partner->fresh()),
            'Partner approved.',
        );
    }

    public function rejectPartner(Request $request, Partner $partner): JsonResponse
    {
        if ($partner->status !== PartnerStatus::Pending) {
            return ApiResponse::error('Partner is not pending.', 422);
        }

        $reason = $request->string('reason')->toString();

        $partner->update(['status' => PartnerStatus::Rejected]);

        return ApiResponse::success(
            new PartnerResource($partner->fresh()),
            "Partner rejected. Reason: {$reason}",
        );
    }

    public function suspendPartner(Request $request, Partner $partner): JsonResponse
    {
        $reason = $request->string('reason')->toString();

        $partner->update(['status' => PartnerStatus::Suspended]);

        return ApiResponse::success(
            new PartnerResource($partner->fresh()),
            "Partner suspended. Reason: {$reason}",
        );
    }
}
