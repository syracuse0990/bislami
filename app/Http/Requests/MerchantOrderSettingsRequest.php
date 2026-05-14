<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantOrderSettingsRequest extends FormRequest
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
            'auto_accept_enabled' => ['required', 'boolean'],
            'auto_reject_unavailable_items' => ['required', 'boolean'],
            'sound_alerts_enabled' => ['required', 'boolean'],
        ];
    }
}