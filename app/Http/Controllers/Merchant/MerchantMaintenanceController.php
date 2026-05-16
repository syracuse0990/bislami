<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantMaintenanceController extends Controller
{
    public function index(Request $request): Response
    {
        $restaurant = $this->resolveRestaurant($request);

        abort_if($restaurant === null, 403, 'No restaurant found.');

        $settings = $restaurant->order_settings ?? [];
        $floorPlan = $settings['floor_plan'] ?? [];

        return Inertia::render('Merchant/Maintenance/Index', [
            'restaurant' => ['id' => $restaurant->id, 'name' => $restaurant->name],
            'isOwner' => $restaurant->user_id === $request->user()->id,
            'discountSettings' => [
                'scDiscountRate'  => (int) ($settings['sc_discount_rate'] ?? 20),
                'pwdDiscountRate' => (int) ($settings['pwd_discount_rate'] ?? 20),
            ],
            'floorPlan' => [
                'room' => [
                    'points' => $floorPlan['room']['points'] ?? $this->defaultRoomPoints(),
                ],
                'tables' => $floorPlan['tables'] ?? [],
                'fixtures' => $floorPlan['fixtures'] ?? [],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sc_discount_rate'  => ['required', 'integer', 'min:0', 'max:100'],
            'pwd_discount_rate' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $restaurant = $this->resolveRestaurant($request);

        abort_if($restaurant === null, 403, 'No restaurant found.');
        abort_unless($restaurant->user_id === $request->user()->id, 403, 'Only the restaurant owner can update settings.');

        $restaurant->update([
            'order_settings' => [
                ...($restaurant->order_settings ?? []),
                'sc_discount_rate'  => $validated['sc_discount_rate'],
                'pwd_discount_rate' => $validated['pwd_discount_rate'],
            ],
        ]);

        return back()->with('success', 'Discount settings updated successfully.');
    }

    public function updateFloorPlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room'               => ['required', 'array'],
            'room.points'        => ['required', 'array', 'min:3', 'max:24'],
            'room.points.*.x'    => ['required', 'numeric', 'min:0'],
            'room.points.*.y'    => ['required', 'numeric', 'min:0'],
            'tables'             => ['required', 'array', 'max:100'],
            'tables.*.id'        => ['required', 'string', 'max:50'],
            'tables.*.name'      => ['required', 'string', 'max:100'],
            'tables.*.seats'     => ['required', 'integer', 'min:1', 'max:50'],
            'tables.*.shape'     => ['required', 'string', 'in:square,round'],
            'tables.*.x'         => ['required', 'numeric', 'min:0'],
            'tables.*.y'         => ['required', 'numeric', 'min:0'],
            'tables.*.w'         => ['required', 'numeric', 'min:48', 'max:320'],
            'tables.*.h'         => ['required', 'numeric', 'min:48', 'max:320'],
            'tables.*.rotation'  => ['required', 'integer', 'in:0,90,180,270'],
            'fixtures'           => ['required', 'array', 'max:100'],
            'fixtures.*.id'      => ['required', 'string', 'max:50'],
            'fixtures.*.type'    => ['required', 'string', 'in:partition,counter'],
            'fixtures.*.name'    => ['required', 'string', 'max:100'],
            'fixtures.*.x'       => ['required', 'numeric', 'min:0'],
            'fixtures.*.y'       => ['required', 'numeric', 'min:0'],
            'fixtures.*.w'       => ['required', 'numeric', 'min:24', 'max:800'],
            'fixtures.*.h'       => ['required', 'numeric', 'min:10', 'max:500'],
        ]);

        $restaurant = $this->resolveRestaurant($request);

        abort_if($restaurant === null, 403, 'No restaurant found.');
        abort_unless($restaurant->user_id === $request->user()->id, 403, 'Only the restaurant owner can update settings.');

        $restaurant->update([
            'order_settings' => [
                ...($restaurant->order_settings ?? []),
                'floor_plan' => [
                    'room' => [
                        'points' => $validated['room']['points'],
                    ],
                    'tables' => $validated['tables'],
                    'fixtures' => $validated['fixtures'],
                ],
            ],
        ]);

        return back()->with('success', 'Floor plan saved successfully.');
    }

    private function defaultRoomPoints(): array
    {
        return [
            ['x' => 18, 'y' => 18],
            ['x' => 862, 'y' => 18],
            ['x' => 862, 'y' => 482],
            ['x' => 18, 'y' => 482],
        ];
    }

    private function resolveRestaurant(Request $request): ?Restaurant
    {
        $user = $request->user();

        $restaurant = $user->managedRestaurants()->first();

        if (! $restaurant) {
            $assignment = $user->staffAssignments()
                ->with('restaurant')
                ->where('status', 'active')
                ->first();

            $restaurant = $assignment?->restaurant;
        }

        return $restaurant;
    }
}
