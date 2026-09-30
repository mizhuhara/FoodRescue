<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PartnerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePartnerProfileRequest;
use App\Http\Resources\Api\V1\PartnerResource;
use App\Models\Partner;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PartnerProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $partner = Partner::where('user_id', request()->user()->id)->firstOrFail();

        return ApiResponse::success(
            new PartnerResource($partner),
            'Partner profile retrieved.'
        );
    }

    public function store(StorePartnerProfileRequest $request): JsonResponse
    {
        $data = $request->safe()->except('logo');

        $partner = Partner::updateOrCreate(
            ['user_id' => $request->user()->id],
            array_merge($data, ['status' => PartnerStatus::Pending])
        );

        if ($request->hasFile('logo')) {
            if ($partner->logo) {
                Storage::disk('public')->delete($partner->logo);
            }
            $partner->logo = $request->file('logo')->store('partners', 'public');
            $partner->save();
        }

        return ApiResponse::success(
            new PartnerResource($partner->fresh()),
            'Partner profile updated.',
            $partner->wasRecentlyCreated ? 201 : 200
        );
    }

    public function dashboard(): JsonResponse
    {
        $partner = Partner::where('user_id', request()->user()->id)->firstOrFail();

        $todayOrders = $partner->orders()
            ->whereDate('created_at', today())
            ->count();

        $todayRevenue = $partner->orders()
            ->whereDate('created_at', today())
            ->where('status', 'COMPLETED')
            ->sum('total');

        $foodRescued = $partner->orders()
            ->where('status', 'COMPLETED')
            ->selectRaw('SUM(order_items.quantity) as total')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->value('total');

        $activeFoods = $partner->foods()
            ->where('status', 'AVAILABLE')
            ->count();

        return ApiResponse::success([
            'orders_today' => $todayOrders,
            'revenue_today' => $todayRevenue ?? 0,
            'food_rescued' => $foodRescued ?? 0,
            'active_foods' => $activeFoods,
        ], 'Partner dashboard.');
    }
}
