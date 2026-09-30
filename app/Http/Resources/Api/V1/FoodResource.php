<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Food $food */
        $food = $this->resource;

        return [
            'id' => $food->id,
            'partner_id' => $food->partner_id,
            'name' => $food->name,
            'slug' => $food->slug,
            'description' => $food->description,
            'image' => $food->image,
            'original_price' => $food->original_price,
            'rescue_price' => $food->rescue_price,
            'discount_percent' => $food->discount_percent,
            'stock' => $food->stock,
            'pickup_start' => $food->pickup_start,
            'pickup_end' => $food->pickup_end,
            'status' => $food->status?->value,
            'category' => CategoryResource::make($food->category),
            'partner' => PartnerResource::make($food->partner),
            'created_at' => $food->created_at?->toISOString(),
            'updated_at' => $food->updated_at?->toISOString(),
        ];
    }
}
