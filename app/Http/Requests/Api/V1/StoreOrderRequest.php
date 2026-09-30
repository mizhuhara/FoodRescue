<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.food_id' => ['required', 'integer', 'exists:foods,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'At least one food item is required.',
            'items.min' => 'At least one food item is required.',
            'items.*.food_id.exists' => 'Invalid food selected.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
