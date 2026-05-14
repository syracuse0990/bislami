<?php

namespace App\Http\Resources\Api;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MenuItem */
class MerchantWorkspaceMenuItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'restaurant' => [
                'id' => $this->restaurant_id,
                'name' => $this->restaurant->name,
            ],
            'imageUrl' => $this->imageUrl(),
            'price' => [
                'value' => (int) $this->price,
                'formatted' => \App\Support\MoneyFormatter::format((int) $this->price),
            ],
            'availability' => [
                'isAvailable' => (bool) $this->is_available,
                'label' => $this->is_available ? 'Live' : 'Paused',
            ],
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'updatedAgo' => $this->updated_at?->diffForHumans(),
        ];
    }
}