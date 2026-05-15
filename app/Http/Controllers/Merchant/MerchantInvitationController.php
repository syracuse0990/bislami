<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantInvitationController extends Controller
{
    public function show(string $token): Response
    {
        $invitation = RestaurantStaff::with(['restaurant', 'invitedBy'])
            ->where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        return Inertia::render('Merchant/Staff/AcceptInvitation', [
            'invitation' => [
                'token' => $invitation->token,
                'restaurantName' => $invitation->restaurant->name,
                'invitedEmail' => $invitation->invited_email,
                'invitedName' => $invitation->invited_name,
                'roleLabel' => RestaurantStaff::ROLE_LABELS[$invitation->role] ?? $invitation->role,
                'invitedBy' => $invitation->invitedBy?->name ?? 'the restaurant owner',
                'invitedAt' => $invitation->invited_at?->diffForHumans(),
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = RestaurantStaff::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        // Promote the user to merchant role so they can access merchant routes.
        $user = $request->user();
        if (! in_array($user->role, ['merchant', 'admin', 'courier'], true)) {
            $user->update(['role' => 'merchant']);
        }

        $invitation->update([
            'user_id' => $user->id,
            'status' => 'active',
            'accepted_at' => now(),
            'token' => null,
        ]);

        return redirect()->route('merchant.staff.index')
            ->with('success', 'Welcome to the team! Your role has been activated.');
    }
}
