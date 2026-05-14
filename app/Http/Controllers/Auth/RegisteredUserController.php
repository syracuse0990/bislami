<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $intended = $request->session()->get('url.intended');
        $hasGuestCart = collect(data_get($request->session()->get('guest_cart', []), 'items', []))->isNotEmpty();

        $validated = $request->validate([
            'account_type' => 'required|in:customer,merchant',
            'name' => 'required|string|max:255',
            'store_name' => 'nullable|required_if:account_type,merchant|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'store_name' => filled($validated['store_name'] ?? null) ? trim($validated['store_name']) : null,
            'email' => $validated['email'],
            'role' => $validated['account_type'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        if (filled($intended)) {
            return redirect()->to($intended);
        }

        if ($hasGuestCart && $user->role === 'customer') {
            return redirect()->route('checkout.index');
        }

        return redirect()->intended(route($user->homeRouteName(), absolute: false));
    }
}
