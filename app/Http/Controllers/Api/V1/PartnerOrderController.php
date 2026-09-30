<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Requests\Api\V1\VerifyPickupRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Partner;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerOrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $partner = Partner::where('user_id', $request->user()->id)->firstOrFail();

        $orders = $partner->orders()
            ->with('items.food', 'user')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders)
            ->additional(['success' => true, 'message' => 'Partner orders retrieved successfully.'])
            ->response();
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->updateStatus($order, OrderStatus::from($request->input('status')));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(new OrderResource($order), 'Order status updated.');
    }

    public function verifyPickup(VerifyPickupRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->verifyPickup($order, $request->input('pickup_code'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(new OrderResource($order), 'Pickup verified, order completed.');
    }
}
