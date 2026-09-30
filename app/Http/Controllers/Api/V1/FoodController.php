<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FoodResource;
use App\Models\Food;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $foods = null;

        if ($sort === 'cheapest') {
            $query->orderBy('rescue_price');
        } elseif ($sort === 'highest_discount') {
            $query->orderByRaw('(original_price - rescue_price) / original_price DESC');
        } elseif ($sort === 'nearest') {
            $user = $request->user();
            if ($user && $user->latitude && $user->longitude) {
                $perPage = $request->integer('per_page', 15);
                $page = $request->integer('page', 1);

                $sorted = $query->get()
                    ->filter(fn ($f) => $f->partner->latitude && $f->partner->longitude)
                    ->sortBy(fn ($f) => $user->distanceToPartner($f->partner) ?? PHP_INT_MAX)
                    ->values();

                $foods = new LengthAwarePaginator(
                    $sorted->forPage($page, $perPage)->values(),
                    $sorted->count(),
                    $perPage,
                    $page,
                    ['path' => $request->url()]
                );
            } else {
                $query->orderBy('pickup_end');
            }
        } else {
            $query->orderBy('pickup_end');
        }

        if (! $foods) {
            $foods = $query->paginate($request->integer('per_page', 15));
        }

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
