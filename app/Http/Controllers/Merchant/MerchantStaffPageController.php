<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantStaff;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantStaffPageController extends Controller
{
    public function index(Request $request): Response
    {
        $restaurants = $request->user()
            ->managedRestaurants()
            ->with(['staff' => fn ($q) => $q->with(['user', 'invitedBy'])->latest()])
            ->orderBy('name')
            ->get();

        $restaurantGroups = $restaurants->map(fn ($restaurant) => [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'activeStaff' => $restaurant->staff
                ->whereIn('status', ['active', 'suspended'])
                ->values()
                ->map(fn (RestaurantStaff $staff) => $this->transformStaff($staff)),
        ])->values();

        return Inertia::render('Merchant/Staff/Index', [
            'restaurantGroups' => $restaurantGroups,
            'roleOptions' => collect(RestaurantStaff::ROLE_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'defaultPermissions' => RestaurantStaff::DEFAULT_PERMISSIONS,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformStaff(RestaurantStaff $staff): array
    {
        return [
            'id' => $staff->id,
            'restaurantId' => $staff->restaurant_id,
            'userId' => $staff->user_id,
            'invitedEmail' => $staff->invited_email,
            'invitedName' => $staff->invited_name,
            'displayName' => $staff->user?->name ?? $staff->invited_name ?? $staff->invited_email,
            'userEmail' => $staff->user?->email ?? $staff->invited_email,
            'role' => $staff->role,
            'roleLabel' => RestaurantStaff::ROLE_LABELS[$staff->role] ?? $staff->role,
            'permissions' => $staff->permissions,
            'status' => $staff->status,
            'token' => $staff->token,
            'invitedByName' => $staff->invitedBy?->name ?? 'Owner',
            'invitedAt' => $staff->invited_at?->diffForHumans(),
            'acceptedAt' => $staff->accepted_at?->diffForHumans(),
        ];
    }
}
