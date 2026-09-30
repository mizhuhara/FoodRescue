<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FoodStatus;
use App\Enums\PartnerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFoodRequest;
use App\Http\Requests\Api\V1\UpdateFoodRequest;
use App\Http\Resources\Api\V1\FoodResource;
use App\Models\Food;
use App\Models\Partner;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerFoodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $partner = $this->partnerFor($request);

        $foods = $partner->foods()
            ->with('category')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return FoodResource::collection($foods)
            ->additional(['success' => true, 'message' => 'Foods retrieved successfully.'])
            ->response();
    }

    public function store(StoreFoodRequest $request): JsonResponse
    {
        $partner = $this->partnerFor($request);

        if ($partner->status !== PartnerStatus::Approved) {
            return ApiResponse::error('Only approved partners can publish food.', 403);
        }

        $data = $request->safe()->except('image');
        $data['partner_id'] = $partner->id;
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        $data['status'] = FoodStatus::Available;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('foods', 'public');
        }

        $food = Food::create($data);

        return ApiResponse::success(
            new FoodResource($food->load('category')),
            'Food created successfully.',
            201
        );
    }

    public function update(UpdateFoodRequest $request, Food $food): JsonResponse
    {
        $data = $request->safe()->except('image');

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(5));
        }

        if ($request->hasFile('image')) {
            if ($food->image) {
                Storage::disk('public')->delete($food->image);
            }
            $data['image'] = $request->file('image')->store('foods', 'public');
        }

        $food->update($data);

        return ApiResponse::success(
            new FoodResource($food->fresh()->load('category')),
            'Food updated successfully.'
        );
    }

    public function destroy(Request $request, Food $food): JsonResponse
    {
        if ($food->partner_id !== $this->partnerFor($request)->id) {
            return ApiResponse::error('You can only delete your own food.', 403);
        }

        if ($food->image) {
            Storage::disk('public')->delete($food->image);
        }

        $food->delete();

        return ApiResponse::success(null, 'Food deleted successfully.');
    }

    private function partnerFor(Request $request): Partner
    {
        return Partner::where('user_id', $request->user()->id)->firstOrFail();
    }
}
