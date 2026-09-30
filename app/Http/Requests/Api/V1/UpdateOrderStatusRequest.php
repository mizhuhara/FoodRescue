<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order !== null && $this->user()?->can('updateStatus', $order) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::enum(OrderStatus::class),
                Rule::notIn([
                    // COMPLETED requires a verified pickup scan, and EXPIRED is set
                    // by the scheduler. Neither may be set manually.
                    OrderStatus::Completed->value,
                    OrderStatus::Expired->value,
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.not_in' => 'This status cannot be set manually.',
        ];
    }
}
