<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerWorkspaceOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'orderNumber' => data_get($this->resource, 'orderNumber'),
            'restaurant' => [
                'id' => data_get($this->resource, 'restaurant.id'),
                'name' => data_get($this->resource, 'restaurant.name'),
                'slug' => data_get($this->resource, 'restaurant.slug'),
            ],
            'status' => [
                'key' => data_get($this->resource, 'status.key'),
                'label' => data_get($this->resource, 'status.label'),
            ],
            'summary' => data_get($this->resource, 'summary'),
            'placedAt' => data_get($this->resource, 'placedAt'),
            'placedAgo' => data_get($this->resource, 'placedAgo'),
            'total' => [
                'value' => data_get($this->resource, 'total.value'),
                'formatted' => data_get($this->resource, 'total.formatted'),
            ],
            'canTrack' => (bool) data_get($this->resource, 'canTrack', false),
            'canReorder' => (bool) data_get($this->resource, 'canReorder', false),
            'reorder' => [
                'visible' => (bool) data_get($this->resource, 'reorder.visible', false),
                'available' => (bool) data_get($this->resource, 'reorder.available', false),
                'label' => data_get($this->resource, 'reorder.label'),
                'description' => data_get($this->resource, 'reorder.description'),
                'items' => collect(data_get($this->resource, 'reorder.items', []))
                    ->map(fn ($item) => [
                        'name' => data_get($item, 'name'),
                        'reason' => data_get($item, 'reason'),
                        'suggestions' => collect(data_get($item, 'suggestions', []))
                            ->map(fn ($suggestion) => [
                                'id' => data_get($suggestion, 'id'),
                                'slug' => data_get($suggestion, 'slug'),
                                'name' => data_get($suggestion, 'name'),
                                'category' => data_get($suggestion, 'category'),
                                'price' => [
                                    'value' => data_get($suggestion, 'price.value'),
                                    'formatted' => data_get($suggestion, 'price.formatted'),
                                ],
                            ])
                            ->values(),
                    ])
                    ->values(),
            ],
        ];
    }
}