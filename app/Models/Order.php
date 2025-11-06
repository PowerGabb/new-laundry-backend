<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'order_number',
        'order_status',
        'payment_status',
        'payment_method',
        'snaptoken',
        'total_amount',
        'subtotal',
        'delivery_fee',
        'discount_amount',
        'discount_code',
        'paid_at',
        'estimated_weight',
        'actual_weight',
        'price_per_kg',
        'items_detail',
        'actual_weight_items',
        'actual_total_amount',
        'special_instructions',
        'notes',
        'customer_latitude',
        'customer_longitude',
        'customer_address',
        'customer_phone',
        'customer_name',
        'pickup_scheduled_time',
        'pickup_scheduled_at',
        'pickup_actual_at',
        'delivery_scheduled_time',
        'delivery_scheduled_at',
        'delivery_actual_at',
        'processing_started_at',
        'processing_completed_at',
        'pickup_biteship_order_id',
        'pickup_biteship_tracking_id',
        'pickup_biteship_waybill_id',
        'pickup_courier_company',
        'pickup_courier_name',
        'pickup_courier_code',
        'pickup_courier_service_name',
        'pickup_courier_service_code',
        'pickup_courier_phone',
        'pickup_courier_tracking_link',
        'pickup_currency',
        'pickup_courier_description',
        'pickup_duration',
        'pickup_shipment_duration_range',
        'pickup_shipment_duration_unit',
        'pickup_shipping_type',
        'pickup_courier_rate',
        'pickup_shipping_fee',
        'pickup_shipping_fee_discount',
        'pickup_shipping_fee_surcharge',
        'pickup_courier_history',
        'delivery_biteship_order_id',
        'delivery_biteship_tracking_id',
        'delivery_biteship_waybill_id',
        'delivery_courier_company',
        'delivery_courier_name',
        'delivery_courier_code',
        'delivery_courier_service_name',
        'delivery_courier_service_code',
        'delivery_courier_phone',
        'delivery_courier_tracking_link',
        'delivery_currency',
        'delivery_courier_description',
        'delivery_duration',
        'delivery_shipment_duration_range',
        'delivery_shipment_duration_unit',
        'delivery_shipping_type',
        'delivery_courier_rate',
        'delivery_shipping_fee',
        'delivery_shipping_fee_discount',
        'delivery_shipping_fee_surcharge',
        'delivery_courier_history',
        'service_type',
        'is_pickup_courier',
        'is_pickup_free',
        'pickup_method',
        'is_delivery_courier',
        'is_delivery_free',
        'delivery_method',
        'pickup_staff_id',
        'delivery_staff_id',
        'photo_before',
        'photo_after',
        'proof_video_url',
        'actual_weight_recorded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'actual_total_amount' => 'integer',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'discount_amount' => 'integer',
            'paid_at' => 'datetime',
            'estimated_weight' => 'integer',
            'actual_weight' => 'integer',
            'price_per_kg' => 'integer',
            'items_detail' => 'array',
            'actual_weight_items' => 'array',
            'customer_latitude' => 'decimal:8',
            'customer_longitude' => 'decimal:8',
            'pickup_scheduled_at' => 'datetime',
            'pickup_actual_at' => 'datetime',
            'delivery_scheduled_at' => 'datetime',
            'delivery_actual_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
            'actual_weight_recorded_at' => 'datetime',
            'pickup_courier_rate' => 'integer',
            'pickup_shipping_fee' => 'integer',
            'pickup_shipping_fee_discount' => 'integer',
            'pickup_shipping_fee_surcharge' => 'integer',
            'pickup_courier_history' => 'array',
            'delivery_courier_rate' => 'integer',
            'delivery_shipping_fee' => 'integer',
            'delivery_shipping_fee_discount' => 'integer',
            'delivery_shipping_fee_surcharge' => 'integer',
            'delivery_courier_history' => 'array',
            'is_pickup_courier' => 'boolean',
            'is_pickup_free' => 'boolean',
            'is_delivery_courier' => 'boolean',
            'is_delivery_free' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch that the order belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the pickup staff assigned to the order.
     */
    public function pickupStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pickup_staff_id');
    }

    /**
     * Get the delivery staff assigned to the order.
     */
    public function deliveryStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_staff_id');
    }
}
