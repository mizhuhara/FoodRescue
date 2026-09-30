<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FoodResource;
use App\Models\Favorite;
use App\Models\Food;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    public function index(): JsonResponse
    {
        $favorites = Favorite::where('user_id', request()->user()->id)
            ->with(['food.category', 'food.partner'])
            ->latest()
            ->get();

        return ApiResponse::success(
            FoodResource::collection($favorites->pluck('food')->filter()),
            'Favorites retrieved successfully.'
        );
    }

    public function store(Food $food): JsonResponse
    {
        $user = request()->user();

        $favorite = Favorite::firstOrCreate(
            ['user_id' => $user->id, 'food_id' => $food->id]
        );

        return ApiResponse::success(
            new FoodResource($food->load(['category', 'partner'])),
            $favorite->wasRecentlyCreated ? 'Added to favorites.' : 'Already in favorites.',
            $favorite->wasRecentlyCreated ? 201 : 200
        );
    }

    public function destroy(Food $food): JsonResponse
    {
        Favorite::where('user_id', request()->user()->id)
            ->where('food_id', $food->id)
            ->delete();

        return ApiResponse::success(null, 'Removed from favorites.');
    }
}
