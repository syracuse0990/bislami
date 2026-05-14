<?php

namespace App\Http\Requests;

use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class MerchantStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'merchant';
    }

    /**
     * @return array<string, array<int, string|File>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'featured_text' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'logo' => ['nullable', File::image()->max(5 * 1024)],
            'location_address' => ['required', 'string', 'max:255'],
            'location_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:location_longitude'],
            'location_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:location_latitude'],
            'delivery_radius_km' => ['nullable', 'numeric', 'min:0.5', 'max:100'],
            'delivery_area_coordinates' => ['nullable', 'array'],
            'delivery_area_coordinates.*.lat' => ['required_with:delivery_area_coordinates.*.lng', 'numeric', 'between:-90,90'],
            'delivery_area_coordinates.*.lng' => ['required_with:delivery_area_coordinates.*.lat', 'numeric', 'between:-180,180'],
            'minimum_order_value' => ['required', 'integer', 'min:0'],
            'preparation_time_min' => ['required', 'integer', 'min:5', 'max:240'],
            'preparation_time_max' => ['required', 'integer', 'min:5', 'max:240'],
            'operating_hours' => ['required', 'array', 'size:7'],
            'operating_hours.*.day' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'operating_hours.*.enabled' => ['required', 'boolean'],
            'operating_hours.*.open' => ['nullable', 'date_format:H:i'],
            'operating_hours.*.close' => ['nullable', 'date_format:H:i'],
            'closure_dates' => ['nullable', 'array'],
            'closure_dates.*' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $preparationMin = (int) $this->input('preparation_time_min', 0);
                $preparationMax = (int) $this->input('preparation_time_max', 0);

                if ($preparationMax < $preparationMin) {
                    $validator->errors()->add('preparation_time_max', 'The maximum preparation time must be greater than or equal to the minimum preparation time.');
                }

                $deliveryAreaCoordinates = collect($this->input('delivery_area_coordinates', []))->filter(fn ($point) => is_array($point));

                if ($deliveryAreaCoordinates->isNotEmpty() && $deliveryAreaCoordinates->count() < 3) {
                    $validator->errors()->add('delivery_area_coordinates', 'Delivery area needs at least three polygon points.');
                }

                $hours = collect($this->input('operating_hours', []));
                $knownDays = collect(Restaurant::defaultOperatingHours())->pluck('day')->all();

                if ($hours->pluck('day')->diff($knownDays)->isNotEmpty() || $hours->pluck('day')->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('operating_hours', 'Operating hours must include each day exactly once.');
                }

                $hours->values()->each(function (array $hour, int $index) use ($validator): void {
                    if (! ($hour['enabled'] ?? false)) {
                        return;
                    }

                    $open = $hour['open'] ?? null;
                    $close = $hour['close'] ?? null;

                    if (! $open) {
                        $validator->errors()->add("operating_hours.{$index}.open", 'Opening time is required for active days.');
                    }

                    if (! $close) {
                        $validator->errors()->add("operating_hours.{$index}.close", 'Closing time is required for active days.');
                    }

                    if (! $open || ! $close) {
                        return;
                    }

                    $openTime = Carbon::createFromFormat('H:i', $open);
                    $closeTime = Carbon::createFromFormat('H:i', $close);

                    if ($closeTime->lessThanOrEqualTo($openTime)) {
                        $validator->errors()->add("operating_hours.{$index}.close", 'Closing time must be after opening time.');
                    }
                });
            },
        ];
    }
}