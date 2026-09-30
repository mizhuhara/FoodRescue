<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $food = $this->route('food');

        return $food && $this->user()?->partner?->id === $food->partner_id;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', File::image()->max('2MB')],
            'original_price' => ['sometimes', 'integer', 'min:1'],
            'rescue_price' => ['sometimes', 'integer', 'min:1', 'lt:original_price'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'pickup_start' => ['sometimes', 'date_format:H:i'],
            'pickup_end' => ['sometimes', 'date_format:H:i', 'after:pickup_start'],
            'status' => ['sometimes', 'in:DRAFT,AVAILABLE,SOLD_OUT,EXPIRED,INACTIVE'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rescue_price.lt' => 'Rescue price must be less than original price.',
            'pickup_end.after' => 'Pickup end time must be after pickup start time.',
        ];
    }
}
