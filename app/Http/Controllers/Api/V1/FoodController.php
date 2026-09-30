<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FoodResource;
use App\Models\Food;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Food::with(['category', 'partner'])
            ->where('status', 'AVAILABLE')
            ->where('stock', '>', 0)
            ->where('pickup_end', '>', now()->format('H:i:s'));

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('partner', fn ($p) => $p->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $sort = $request->string('sort')->toString();

        // ponytail: distance/rating/price filters need Phase 6 location + Phase 8 reviews. Add then.
        if ($sort === 'cheapest') {
            $query->orderBy('rescue_price');
        } elseif ($sort === 'highest_discount') {
            $query->orderByRaw('(original_price - rescue_price) / original_price DESC');
        } else {
            $query->orderBy('pickup_end');
        }

        $foods = $query->paginate($request->integer('per_page', 15));

        return FoodResource::collection($foods)
            ->additional(['success' => true, 'message' => 'Foods retrieved successfully.'])
            ->response();
    }

    public function show(Food $food): JsonResponse
    {
        $food->load(['category', 'partner']);

        return ApiResponse::success(
            new FoodResource($food),
            'Food retrieved successfully.'
        );
    }
}
