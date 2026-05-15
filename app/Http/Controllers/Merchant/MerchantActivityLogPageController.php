<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantActivityLogPageController extends Controller
{
    public function index(Request $request): Response
    {
        $restaurantIds = $request->user()->managedRestaurants()->pluck('id');

        $logs = ActivityLog::query()
            ->with('user')
            ->whereIn('restaurant_id', $restaurantIds)
            ->latest('created_at')
            ->paginate(50)
            ->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'actorName' => $log->user?->name ?? 'Deleted user',
                'subjectType' => $log->subject_type,
                'subjectId' => $log->subject_id,
                'metadata' => $log->metadata,
                'createdAt' => $log->created_at->diffForHumans(),
                'createdAtFull' => $log->created_at->format('M j, Y \a\t g:i A'),
            ]);

        return Inertia::render('Merchant/Staff/Activity', [
            'logs' => $logs,
        ]);
    }
}
