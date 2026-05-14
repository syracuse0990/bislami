<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Support\CustomerCartService;
use App\Support\GuestCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicCartController extends Controller
{
    public function index(
        Request $request,
        GuestCartService $guestCartService,
        CustomerCartService $customerCartService,
    ): Response|RedirectResponse {
        if ($request->user()?->role === 'customer') {
            if ($guestCartService->hasItems($request)) {
                $guestCartService->mergeIntoCustomer($request, $request->user(), $customerCartService);
            }

            return redirect()->route('customer.cart.index');
        }

        if ($request->user()) {
            return redirect()->route($request->user()->homeRouteName())
                ->with('error', 'Only customer accounts can place food orders.');
        }

        return Inertia::render('Cart/Index', [
            'cart' => $guestCartService->payload($request),
            'canLogin' => true,
            'canRegister' => true,
        ]);
    }

    public function store(Request $request, MenuItem $menuItem, GuestCartService $guestCartService): RedirectResponse
    {
        $menuItem->loadMissing('restaurant');

        abort_unless($menuItem->is_available && $menuItem->restaurant?->is_visible, 404);

        if ($request->user()?->role === 'customer') {
            return redirect()->route('customer.restaurants.show', $menuItem->restaurant)
                ->with('error', 'Signed-in customers should place orders from the customer workspace.');
        }

        if ($request->user()) {
            return redirect()->route($request->user()->homeRouteName())
                ->with('error', 'Only customer accounts can place food orders.');
        }

        $cartContext = $guestCartService->context($request);
        $willReplaceCart = $guestCartService->willReplaceCart($request, $menuItem);

        if ($willReplaceCart && ! $request->boolean('replace_cart')) {
            return $this->redirectToRequestedPath($request, route('cart.index'))
                ->with('error', 'Your cart already contains items from '.$cartContext['restaurantName'].'. Confirm replacing it before adding '.$menuItem->name.'.');
        }

        $guestCartService->addMenuItem($request, $menuItem, $request->boolean('replace_cart'));

        $message = $menuItem->name.' added to cart.';

        if ($willReplaceCart) {
            $message .= ' Your previous cart from '.$cartContext['restaurantName'].' was replaced.';
        }

        return $this->redirectToRequestedPath($request, route('cart.index'))
            ->with('success', $message);
    }

    public function increment(Request $request, MenuItem $menuItem, GuestCartService $guestCartService): RedirectResponse
    {
        if ($request->user()?->role === 'customer') {
            return redirect()->route('customer.cart.index');
        }

        if ($request->user()) {
            return redirect()->route($request->user()->homeRouteName())
                ->with('error', 'Only customer accounts can place food orders.');
        }

        $menuItem->loadMissing('restaurant');
        abort_unless($menuItem->is_available && $menuItem->restaurant?->is_visible, 404);

        $guestCartService->increment($request, $menuItem);

        return redirect()->route('cart.index');
    }

    public function decrement(Request $request, MenuItem $menuItem, GuestCartService $guestCartService): RedirectResponse
    {
        if ($request->user()?->role === 'customer') {
            return redirect()->route('customer.cart.index');
        }

        if ($request->user()) {
            return redirect()->route($request->user()->homeRouteName())
                ->with('error', 'Only customer accounts can place food orders.');
        }

        $guestCartService->decrement($request, $menuItem);

        return redirect()->route('cart.index');
    }

    public function checkout(
        Request $request,
        GuestCartService $guestCartService,
        CustomerCartService $customerCartService,
    ): RedirectResponse {
        if ($request->user()?->role === 'customer') {
            if ($guestCartService->hasItems($request)) {
                $guestCartService->mergeIntoCustomer($request, $request->user(), $customerCartService);
            }

            return redirect()->route('customer.checkout.index');
        }

        if ($request->user()) {
            return redirect()->route($request->user()->homeRouteName())
                ->with('error', 'Only customer accounts can checkout food orders.');
        }

        if (! $guestCartService->hasItems($request)) {
            return redirect()->route('cart.index');
        }

        return redirect()->guest(route('login'));
    }

    private function redirectToRequestedPath(Request $request, string $fallback): RedirectResponse
    {
        return redirect()->to($this->requestedRedirectPath($request, $fallback));
    }

    private function requestedRedirectPath(Request $request, string $fallback): string
    {
        $target = $request->string('redirect_to')->toString();

        if ($target !== '' && Str::startsWith($target, '/') && ! Str::startsWith($target, '//')) {
            return $target;
        }

        return $fallback;
    }
}