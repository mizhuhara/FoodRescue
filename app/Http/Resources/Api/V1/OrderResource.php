<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status?->value,
            'payment_status' => $order->payment_status?->value,
            'subtotal' => $order->subtotal,
            'total' => $order->total,
            'pickup_code' => $order->pickup_code,
            'pickup_completed_at' => $order->pickup_completed_at?->toISOString(),
            'expires_at' => $order->expires_at?->toISOString(),
            'partner' => PartnerResource::make($order->partner),
            'items' => OrderItemResource::collection($order->items),
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
        ];
    }
}
