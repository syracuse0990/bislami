<?php

namespace App\Http\Requests;

use App\Models\RestaurantStaff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MerchantStaffUpdateRequest extends FormRequest
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
            'role' => ['sometimes', 'required', Rule::in(RestaurantStaff::ROLES)],
            'permissions' => ['sometimes', 'required', 'array'],
            'permissions.menu_edit' => ['boolean'],
            'permissions.order_access' => ['boolean'],
            'permissions.finance_access' => ['boolean'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'suspended'])],
        ];
    }
}
