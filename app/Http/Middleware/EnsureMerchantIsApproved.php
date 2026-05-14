<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantIsApproved
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'merchant' || $user->isMerchantVerified()) {
            return $next($request);
        }

        $message = 'Your merchant account is awaiting approval. BizLami will unlock menu and order tools after review.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        return redirect()
            ->route('merchant.dashboard')
            ->with('error', $message);
    }
}