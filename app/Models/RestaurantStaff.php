<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'restaurant_id',
    'user_id',
    'invited_email',
    'invited_name',
    'role',
    'permissions',
    'status',
    'token',
    'invited_by',
    'invited_at',
    'accepted_at',
])]
class RestaurantStaff extends Model
{
    public const ROLES = ['manager', 'kitchen', 'cashier', 'waiter'];

    public const ROLE_LABELS = [
        'manager' => 'Manager',
        'kitchen' => 'Kitchen',
        'cashier' => 'Cashier',
        'waiter' => 'Waiter',
    ];

    public const DEFAULT_PERMISSIONS = [
        'manager' => ['menu_edit' => true,  'order_access' => true,  'finance_access' => true],
        'kitchen' => ['menu_edit' => false, 'order_access' => true,  'finance_access' => false],
        'cashier' => ['menu_edit' => false, 'order_access' => true,  'finance_access' => true],
        'waiter'  => ['menu_edit' => false, 'order_access' => true,  'finance_access' => false],
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
