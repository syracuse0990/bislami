<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'store_name', 'email', 'email_verified_at', 'role', 'password', 'oauth_provider', 'oauth_provider_id', 'merchant_verified_at', 'is_suspended', 'suspended_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'merchant_verified_at' => 'datetime',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_suspended', false);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function managedRestaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function restaurantProfile(): HasOne
    {
        return $this->hasOne(Restaurant::class)->oldestOfMany();
    }

    public function assignedDeliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_id');
    }

    public function homeRouteName(): string
    {
        return match ($this->role) {
            'merchant' => 'merchant.dashboard',
            'courier' => 'courier.dashboard',
            'admin' => 'admin.dashboard',
            default => 'customer.dashboard',
        };
    }

    public function isMerchantVerified(): bool
    {
        return $this->merchant_verified_at !== null;
    }

    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }
}
