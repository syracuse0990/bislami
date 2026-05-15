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

        // Not a merchant, already verified owner, or an active staff member → allow through.
        if (! $user || $user->role !== 'merchant' || $user->isMerchantVerified()) {
            return $next($request);
        }

        if ($user->staffAssignments()->where('status', 'active')->exists()) {
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