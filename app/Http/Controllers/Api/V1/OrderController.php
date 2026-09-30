<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items.food', 'partner')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders)
            ->additional(['success' => true, 'message' => 'Orders retrieved successfully.'])
            ->response();
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load('items.food', 'partner');

        return ApiResponse::success(
            new OrderResource($order),
            'Order retrieved successfully.'
        );
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->create(
                $request->user(),
                $request->safe()->array('items')
            );

            return ApiResponse::success(
                new OrderResource($order->load('items.food', 'partner')),
                'Order created successfully.',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function cancel(Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        try {
            $order = $this->orderService->updateStatus($order, OrderStatus::Cancelled);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(
            new OrderResource($order->fresh()),
            'Order cancelled successfully.'
        );
    }
}
