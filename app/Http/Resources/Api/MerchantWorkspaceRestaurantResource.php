<?php

namespace App\Http\Resources\Api;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Restaurant */
class MerchantWorkspaceRestaurantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'cuisine' => $this->cuisine,
            'featuredText' => $this->featured_text,
            'rating' => [
                'value' => (float) $this->rating,
                'label' => number_format((float) $this->rating, 1).' rating',
            ],
            'deliveryWindow' => [
                'min' => (int) $this->min_delivery_time,
                'max' => (int) $this->max_delivery_time,
                'label' => $this->min_delivery_time.'-'.$this->max_delivery_time.' min',
            ],
            'deliveryFee' => [
                'value' => (int) $this->delivery_fee,
                'formatted' => \App\Support\MoneyFormatter::format((int) $this->delivery_fee),
                'label' => $this->delivery_fee > 0
                    ? \App\Support\MoneyFormatter::format((int) $this->delivery_fee).' delivery'
                    : 'Free delivery',
            ],
            'visibility' => [
                'isVisible' => (bool) $this->is_visible,
                'label' => $this->is_visible ? 'Visible to customers' : 'Hidden from discovery',
            ],
            'counts' => [
                'menuItems' => (int) $this->menu_items_count,
                'liveMenuItems' => (int) $this->live_menu_items_count,
                'pausedMenuItems' => (int) $this->paused_menu_items_count,
                'activeOrders' => (int) $this->active_orders_count,
            ],
        ];
    }
}