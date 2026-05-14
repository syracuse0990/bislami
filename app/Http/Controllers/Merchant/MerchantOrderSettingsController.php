<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantOrderSettingsRequest;
use App\Models\Restaurant;
use App\Support\OrderLifecycle;
use Illuminate\Http\RedirectResponse;

class MerchantOrderSettingsController extends Controller
{
    public function update(MerchantOrderSettingsRequest $request, Restaurant $restaurant): RedirectResponse
    {
        abort_unless($restaurant->user_id === $request->user()->id, 404);

        $restaurant->update([
            'order_settings' => [
                ...OrderLifecycle::defaultOrderSettings(),
                ...$request->validated(),
            ],
        ]);

        return back()->with('success', 'Restaurant order settings updated.');
    }
}