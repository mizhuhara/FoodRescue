<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->partner()->exists() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', File::image()->max('2MB')],
            'original_price' => ['required', 'integer', 'min:1'],
            'rescue_price' => ['required', 'integer', 'min:1', 'lt:original_price'],
            'stock' => ['required', 'integer', 'min:1'],
            'pickup_start' => ['required', 'date_format:H:i'],
            'pickup_end' => ['required', 'date_format:H:i', 'after:pickup_start'],
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
