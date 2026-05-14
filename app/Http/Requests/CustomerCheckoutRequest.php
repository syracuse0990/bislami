<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerCheckoutRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'fulfillment_type' => $this->input('fulfillment_type', 'delivery'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->role === 'customer';
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $fulfillmentType = $this->input('fulfillment_type', 'delivery');

        return [
            'fulfillment_type' => ['required', Rule::in(['delivery', 'pickup'])],
            'delivery_address' => [Rule::requiredIf($fulfillmentType === 'delivery'), 'nullable', 'string', 'max:255'],
            'delivery_latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
                Rule::requiredIf($fulfillmentType === 'delivery' && filled($this->input('delivery_longitude'))),
            ],
            'delivery_longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
                Rule::requiredIf($fulfillmentType === 'delivery' && filled($this->input('delivery_latitude'))),
            ],
            'idempotency_key' => ['required', 'uuid'],
            'payment_method' => ['required', 'string', 'max:255'],
            'driver_notes' => ['nullable', 'string', 'max:1000'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ];
    }
}