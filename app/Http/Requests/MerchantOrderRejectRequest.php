<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MerchantOrderRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'merchant';
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'rejection_reason_code' => [
                'required',
                Rule::in(['busy_capacity', 'item_unavailable', 'restaurant_closed', 'customer_request', 'other']),
            ],
            'rejection_reason_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}