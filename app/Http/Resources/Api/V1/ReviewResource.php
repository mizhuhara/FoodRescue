<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Review $review */
        $review = $this->resource;

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'image' => $review->image,
            'user' => $review->user ? [
                'id' => $review->user->id,
                'name' => $review->user->name,
                'avatar' => $review->user->avatar,
            ] : null,
            'food' => $review->food ? [
                'id' => $review->food->id,
                'name' => $review->food->name,
                'image' => $review->food->image,
            ] : null,
            'created_at' => $review->created_at?->toISOString(),
        ];
    }
}
