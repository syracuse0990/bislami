<?php

namespace App\Http\Requests;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class MerchantMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'merchant';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'availability_starts_at' => $this->normalizeAvailabilityTime($this->input('availability_starts_at')),
            'availability_ends_at' => $this->normalizeAvailabilityTime($this->input('availability_ends_at')),
        ]);
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
            'pax_min' => ['nullable', 'integer', 'min:1', 'max:999'],
            'pax_max' => ['nullable', 'integer', 'min:1', 'max:999', 'gte:pax_min'],
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

    private function normalizeAvailabilityTime(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        if ($normalized === '') {
            return null;
        }

        foreach (['H:i', 'H:i:s', 'g:i A', 'g:i:s A', 'g:i a', 'g:i:s a', 'h:i A', 'h:i:s A', 'h:i a', 'h:i:s a'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $normalized);

            if ($date !== false) {
                return $date->format('H:i');
            }
        }

        return $normalized;
    }
}