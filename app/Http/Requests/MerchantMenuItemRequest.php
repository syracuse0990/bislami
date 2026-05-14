<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class MerchantMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'merchant';
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'image' => ['nullable', File::image()->max(5 * 1024)],
            'price' => ['required', 'integer', 'min:1'],
            'promo_price' => ['nullable', 'integer', 'min:1', 'lt:price'],
            'is_available' => ['required', 'boolean'],
            'availability_starts_at' => ['nullable', 'date_format:H:i', 'required_with:availability_ends_at'],
            'availability_ends_at' => ['nullable', 'date_format:H:i', 'required_with:availability_starts_at', 'different:availability_starts_at'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.price_delta' => ['required', 'integer', 'min:0'],
            'add_ons' => ['nullable', 'array'],
            'add_ons.*.name' => ['required', 'string', 'max:255'],
            'add_ons.*.price' => ['required', 'integer', 'min:0'],
            'modifiers' => ['nullable', 'array'],
            'modifiers.*.name' => ['required', 'string', 'max:255'],
            'modifiers.*.options' => ['required', 'array', 'min:1'],
            'modifiers.*.options.*' => ['required', 'string', 'max:120'],
            'bundle_items' => ['nullable', 'array'],
            'bundle_items.*.name' => ['required', 'string', 'max:255'],
            'bundle_items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}