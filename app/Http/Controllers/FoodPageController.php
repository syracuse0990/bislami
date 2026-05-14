<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Support\CustomerCatalogData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class FoodPageController extends Controller
{
    public function show(Request $request, MenuItem $menuItem, CustomerCatalogData $catalogData): Response|RedirectResponse
    {
        $menuItem->loadMissing('restaurant');

        abort_unless($menuItem->is_available && $menuItem->restaurant?->is_visible, 404);

        if ($request->user()?->role === 'customer') {
            return redirect()->to(route('customer.restaurants.show', $menuItem->restaurant).'#dish-'.$menuItem->id);
        }

        return Inertia::render('Foods/Show', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'food' => $catalogData->foodDetail($menuItem),
        ]);
    }
}