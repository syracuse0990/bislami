<?php

use App\Models\Restaurant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('merchant.orders.{restaurantId}', function ($user, int $restaurantId): bool {
    return $user->role === 'merchant'
        && Restaurant::query()
            ->whereKey($restaurantId)
            ->where('user_id', $user->id)
            ->exists();
});