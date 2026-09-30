<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Food;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function index(Food $food): JsonResponse
    {
        $reviews = $food->reviews()
            ->with(['user', 'food'])
            ->latest()
            ->paginate(request()->integer('per_page', 15));

        return ReviewResource::collection($reviews)
            ->additional(['success' => true, 'message' => 'Reviews retrieved successfully.'])
            ->response();
    }

    public function store(StoreReviewRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            return ApiResponse::error('You can only review your own orders.', 403);
        }

        if ($order->status !== OrderStatus::Completed) {
            return ApiResponse::error('You can only review completed orders.', 422);
        }

        if ($order->review()->exists()) {
            return ApiResponse::error('This order has already been reviewed.', 422);
        }

        $item = $order->items()->where('food_id', $request->integer('food_id'))->first();

        if (! $item) {
            return ApiResponse::error('This food was not part of the order.', 422);
        }

        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('reviews', 'public');
        }

        $review = $order->review()->create($data + ['user_id' => $user->id]);

        return ApiResponse::success(
            new ReviewResource($review->load(['user', 'food'])),
            'Review submitted.',
            201
        );
    }
}
