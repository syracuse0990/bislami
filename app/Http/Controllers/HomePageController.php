<?php

namespace App\Http\Controllers;

use App\Support\CustomerCatalogData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class HomePageController extends Controller
{
    public function __invoke(Request $request, CustomerCatalogData $catalogData): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($request->user()->homeRouteName());
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'cuisine' => $request->string('cuisine')->toString(),
                'category' => $request->string('category')->toString(),
            ],
            ...$catalogData->publicHome([
                'search' => $request->string('search')->toString(),
                'cuisine' => $request->string('cuisine')->toString(),
                'category' => $request->string('category')->toString(),
            ]),
        ]);
    }
}