<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ImpactController extends Controller
{
    public function customerImpact(): JsonResponse
    {
        $user = request()->user();

        $foodCount = $user->orders()
            ->where('status', 'COMPLETED')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum('order_items.quantity');

        $ordersCount = $user->orders()
            ->where('status', 'COMPLETED')
            ->count();

        $totalSpent = $user->orders()
            ->where('status', 'COMPLETED')
            ->sum('total');

        $avgRating = $user->reviews()
            ->average('rating');

        return ApiResponse::success([
            'food_rescued' => $foodCount ?? 0,
            'orders_completed' => $ordersCount,
            'total_spent' => $totalSpent ?? 0,
            'avg_rating' => $avgRating ? round($avgRating, 2) : 0,
        ], 'Customer impact retrieved.');
    }

    public function partnerImpact(): JsonResponse
    {
        $user = request()->user();
        $partner = $user->partner;

        if (! $partner) {
            return ApiResponse::error('Partner profile not found.', 404);
        }

        $foodRescued = $partner->orders()
            ->where('status', 'COMPLETED')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum('order_items.quantity');

        $ordersCompleted = $partner->orders()
            ->where('status', 'COMPLETED')
            ->count();

        $revenue = $partner->orders()
            ->where('status', 'COMPLETED')
            ->sum('total');

        $avgRating = $partner->foods()
            ->join('reviews', 'foods.id', '=', 'reviews.food_id')
            ->average('reviews.rating');

        return ApiResponse::success([
            'food_rescued' => $foodRescued ?? 0,
            'orders_completed' => $ordersCompleted,
            'revenue' => $revenue ?? 0,
            'avg_rating' => $avgRating ? round($avgRating, 2) : 0,
        ], 'Partner impact retrieved.');
    }
}
