<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantStaffInviteRequest;
use App\Http\Requests\MerchantStaffUpdateRequest;
use App\Mail\StaffWelcomeMail;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class MerchantStaffController extends Controller
{
    public function store(MerchantStaffInviteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $restaurant = $request->user()
            ->managedRestaurants()
            ->findOrFail($validated['restaurant_id']);

        // Find existing user or create one with a temporary password
        $temporaryPassword = null;
        $user = User::where('email', $validated['invited_email'])->first();

        if (! $user) {
            $temporaryPassword = 'Staff@' . rand(1000, 9999);
            $user = User::create([
                'name' => $validated['invited_name'] ?? explode('@', $validated['invited_email'])[0],
                'email' => $validated['invited_email'],
                'password' => Hash::make($temporaryPassword),
                'email_verified_at' => now(),
            ]);
        }

        RestaurantStaff::create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'invited_email' => $validated['invited_email'],
            'invited_name' => $validated['invited_name'] ?? null,
            'role' => $validated['role'],
            'permissions' => RestaurantStaff::DEFAULT_PERMISSIONS[$validated['role']],
            'status' => 'active',
            'invited_by' => $request->user()->id,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        Mail::to($validated['invited_email'])->send(new StaffWelcomeMail(
            recipientName: $user->name,
            restaurantName: $restaurant->name,
            roleLabel: RestaurantStaff::ROLE_LABELS[$validated['role']] ?? $validated['role'],
            loginEmail: $validated['invited_email'],
            temporaryPassword: $temporaryPassword,
        ));

        return back()->with('success', 'Team member added. Login credentials have been sent to their email.');
    }

    public function update(MerchantStaffUpdateRequest $request, RestaurantStaff $staff): RedirectResponse
    {
        abort_unless(
            $request->user()->managedRestaurants()->whereKey($staff->restaurant_id)->exists(),
            403,
        );

        $staff->update($request->validated());

        return back()->with('success', 'Staff member updated.');
    }

    public function destroy(Request $request, RestaurantStaff $staff): RedirectResponse
    {
        abort_unless(
            $request->user()->managedRestaurants()->whereKey($staff->restaurant_id)->exists(),
            403,
        );

        $staff->delete();

        return back()->with('success', 'Staff member removed.');
    }
}
