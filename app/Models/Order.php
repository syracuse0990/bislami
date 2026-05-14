<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'courier_id',
    'restaurant_id',
    'status',
    'fulfillment_type',
    'subtotal',
    'delivery_fee',
    'service_fee',
    'total',
    'payment_method',
    'idempotency_key',
    'delivery_address',
    'delivery_latitude',
    'delivery_longitude',
    'driver_notes',
    'customer_notes',
    'merchant_notes',
    'rejection_reason_code',
    'rejection_reason_note',
    'placed_at',
    'scheduled_for',
    'accepted_at',
    'preparing_at',
    'ready_at',
    'picked_up_at',
    'delivered_at',
    'rejected_at',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'placed_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'accepted_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}