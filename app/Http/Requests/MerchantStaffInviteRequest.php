<?php

namespace App\Http\Requests;

use App\Models\RestaurantStaff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MerchantStaffInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'integer'],
            'invited_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('restaurant_staff', 'invited_email')
                    ->where(fn ($q) => $q->where('restaurant_id', $this->input('restaurant_id'))),
            ],
            'invited_name' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in(RestaurantStaff::ROLES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invited_email.unique' => 'This email is already a member of this restaurant.',
        ];
    }
}
