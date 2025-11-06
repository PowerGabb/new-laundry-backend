<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'snaptoken' => $this->snaptoken,
            'total_amount' => $this->total_amount,
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'discount_amount' => $this->discount_amount,
            'discount_code' => $this->discount_code,
            'paid_at' => $this->paid_at?->toIso8601String(),

            // Items info
            'estimated_weight' => $this->estimated_weight,
            'actual_weight' => $this->actual_weight,
            'actual_weight_items' => $this->actual_weight_items,
            'actual_total_amount' => $this->actual_total_amount,
            'proof_video_url' => $this->proof_video_url,
            'actual_weight_recorded_at' => $this->actual_weight_recorded_at?->toIso8601String(),
            'price_per_kg' => $this->price_per_kg,
            'items_detail' => $this->items_detail,
            'special_instructions' => $this->special_instructions,
            'notes' => $this->notes,

            // Customer info
            'customer_latitude' => $this->customer_latitude,
            'customer_longitude' => $this->customer_longitude,
            'customer_address' => $this->customer_address,
            'customer_phone' => $this->customer_phone,
            'customer_name' => $this->customer_name,

            // Scheduling
            'pickup_scheduled_time' => $this->pickup_scheduled_time,
            'pickup_scheduled_at' => $this->pickup_scheduled_at?->toIso8601String(),
            'pickup_actual_at' => $this->pickup_actual_at?->toIso8601String(),
            'delivery_scheduled_time' => $this->delivery_scheduled_time,
            'delivery_scheduled_at' => $this->delivery_scheduled_at?->toIso8601String(),
            'delivery_actual_at' => $this->delivery_actual_at?->toIso8601String(),
            'processing_started_at' => $this->processing_started_at?->toIso8601String(),
            'processing_completed_at' => $this->processing_completed_at?->toIso8601String(),

            // Pickup courier info
            'pickup' => [
                'biteship_order_id' => $this->pickup_biteship_order_id,
                'biteship_tracking_id' => $this->pickup_biteship_tracking_id,
                'biteship_waybill_id' => $this->pickup_biteship_waybill_id,
                'courier_company' => $this->pickup_courier_company,
                'courier_name' => $this->pickup_courier_name,
                'courier_code' => $this->pickup_courier_code,
                'courier_service_name' => $this->pickup_courier_service_name,
                'courier_service_code' => $this->pickup_courier_service_code,
                'courier_phone' => $this->pickup_courier_phone,
                'courier_tracking_link' => $this->pickup_courier_tracking_link,
                'currency' => $this->pickup_currency,
                'courier_description' => $this->pickup_courier_description,
                'duration' => $this->pickup_duration,
                'shipment_duration_range' => $this->pickup_shipment_duration_range,
                'shipment_duration_unit' => $this->pickup_shipment_duration_unit,
                'shipping_type' => $this->pickup_shipping_type,
                'courier_rate' => $this->pickup_courier_rate,
                'shipping_fee' => $this->pickup_shipping_fee,
                'shipping_fee_discount' => $this->pickup_shipping_fee_discount,
                'shipping_fee_surcharge' => $this->pickup_shipping_fee_surcharge,
                'courier_history' => $this->pickup_courier_history,
            ],

            // Delivery courier info
            'delivery' => [
                'biteship_order_id' => $this->delivery_biteship_order_id,
                'biteship_tracking_id' => $this->delivery_biteship_tracking_id,
                'biteship_waybill_id' => $this->delivery_biteship_waybill_id,
                'courier_company' => $this->delivery_courier_company,
                'courier_name' => $this->delivery_courier_name,
                'courier_code' => $this->delivery_courier_code,
                'courier_service_name' => $this->delivery_courier_service_name,
                'courier_service_code' => $this->delivery_courier_service_code,
                'courier_phone' => $this->delivery_courier_phone,
                'courier_tracking_link' => $this->delivery_courier_tracking_link,
                'currency' => $this->delivery_currency,
                'courier_description' => $this->delivery_courier_description,
                'duration' => $this->delivery_duration,
                'shipment_duration_range' => $this->delivery_shipment_duration_range,
                'shipment_duration_unit' => $this->delivery_shipment_duration_unit,
                'shipping_type' => $this->delivery_shipping_type,
                'courier_rate' => $this->delivery_courier_rate,
                'shipping_fee' => $this->delivery_shipping_fee,
                'shipping_fee_discount' => $this->delivery_shipping_fee_discount,
                'shipping_fee_surcharge' => $this->delivery_shipping_fee_surcharge,
                'courier_history' => $this->delivery_courier_history,
            ],

            // Methods
            'service_type' => $this->service_type,
            'is_pickup_courier' => $this->is_pickup_courier,
            'is_pickup_free' => $this->is_pickup_free,
            'pickup_method' => $this->pickup_method,
            'is_delivery_courier' => $this->is_delivery_courier,
            'is_delivery_free' => $this->is_delivery_free,
            'delivery_method' => $this->delivery_method,

            // Staff
            'pickup_staff_id' => $this->pickup_staff_id,
            'delivery_staff_id' => $this->delivery_staff_id,
            'pickup_staff' => $this->whenLoaded('pickupStaff'),
            'delivery_staff' => $this->whenLoaded('deliveryStaff'),

            // Photos
            'photo_before' => $this->photo_before,
            'photo_after' => $this->photo_after,

            // Relations
            'user' => new UserResource($this->whenLoaded('user')),
            'branch' => new BranchResource($this->whenLoaded('branch')),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
