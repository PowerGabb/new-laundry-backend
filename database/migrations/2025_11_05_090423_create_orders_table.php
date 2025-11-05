<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
             $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            // order basic info
            $table->string('order_number', 100)->unique();
            $table->string('order_status', 50)->default('pending'); // pending, processing, washing, ready, picked_up, delivering, completed, cancelled

            // payment info
            $table->string('payment_status', 50)->default('unpaid'); // unpaid, pending, paid, failed, refunded
            $table->string('payment_method', 50)->nullable(); // cash, midtrans
            $table->string('snaptoken', 255)->nullable();
            $table->integer('total_amount');
            $table->integer('subtotal')->nullable();
            $table->integer('delivery_fee')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->string('discount_code', 50)->nullable();
            $table->timestamp('paid_at')->nullable();

            // items info
            $table->integer('estimated_weight')->nullable(); // berat estimasi saat order
            $table->integer('actual_weight')->nullable(); // berat actual setelah ditimbang
            $table->integer('price_per_kg')->nullable();
            $table->text('items_detail')->nullable(); // JSON untuk detail item (jenis pakaian, jumlah, dll)
            $table->text('special_instructions')->nullable();
            $table->text('notes')->nullable();

            // customer location & contact
            $table->decimal('customer_latitude', 10, 8)->nullable();
            $table->decimal('customer_longitude', 11, 8)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_name', 100)->nullable();

            // pickup & delivery scheduling
            $table->string('pickup_scheduled_time')->nullable(); // waktu dijadwalkan pickup (string dari user, misal: "09:00-12:00")
            $table->timestamp('pickup_scheduled_at')->nullable();
            $table->timestamp('pickup_actual_at')->nullable();
            $table->string('delivery_scheduled_time')->nullable(); // waktu dijadwalkan delivery (string dari user)
            $table->timestamp('delivery_scheduled_at')->nullable();
            $table->timestamp('delivery_actual_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();

            // biteship courier info - PICKUP (user -> laundry)
            $table->string('pickup_biteship_order_id')->nullable();
            $table->string('pickup_biteship_tracking_id')->nullable();
            $table->string('pickup_biteship_waybill_id')->nullable();
            $table->string('pickup_courier_company', 50)->nullable(); // gojek, grab (company field)
            $table->string('pickup_courier_name', 100)->nullable(); // GOJEK, GRAB
            $table->string('pickup_courier_code', 50)->nullable(); // gojek, grab
            $table->string('pickup_courier_service_name', 100)->nullable(); // Instant, Same Day
            $table->string('pickup_courier_service_code', 50)->nullable(); // instant, same_day
            $table->string('pickup_courier_phone', 20)->nullable();
            $table->string('pickup_courier_tracking_link')->nullable();
            $table->string('pickup_currency', 10)->default('IDR');
            $table->text('pickup_courier_description')->nullable();
            $table->string('pickup_duration')->nullable(); // "1 - 2 Hours"
            $table->string('pickup_shipment_duration_range')->nullable(); // "1 - 2"
            $table->string('pickup_shipment_duration_unit', 20)->nullable(); // hours, days
            $table->string('pickup_shipping_type', 50)->nullable(); // parcel, document
            $table->integer('pickup_courier_rate')->nullable(); // price dari courier
            $table->integer('pickup_shipping_fee')->nullable();
            $table->integer('pickup_shipping_fee_discount')->default(0);
            $table->integer('pickup_shipping_fee_surcharge')->default(0);
            $table->text('pickup_courier_history')->nullable(); // JSON tracking history

            // biteship courier info - DELIVERY (laundry -> user)
            $table->string('delivery_biteship_order_id')->nullable();
            $table->string('delivery_biteship_tracking_id')->nullable();
            $table->string('delivery_biteship_waybill_id')->nullable();
            $table->string('delivery_courier_company', 50)->nullable(); // gojek, grab
            $table->string('delivery_courier_name', 100)->nullable(); // GOJEK, GRAB
            $table->string('delivery_courier_code', 50)->nullable(); // gojek, grab
            $table->string('delivery_courier_service_name', 100)->nullable(); // Instant, Same Day
            $table->string('delivery_courier_service_code', 50)->nullable(); // instant, same_day
            $table->string('delivery_courier_phone', 20)->nullable();
            $table->string('delivery_courier_tracking_link')->nullable();
            $table->string('delivery_currency', 10)->default('IDR');
            $table->text('delivery_courier_description')->nullable();
            $table->string('delivery_duration')->nullable(); // "1 - 2 Hours"
            $table->string('delivery_shipment_duration_range')->nullable(); // "1 - 2"
            $table->string('delivery_shipment_duration_unit', 20)->nullable(); // hours, days
            $table->string('delivery_shipping_type', 50)->nullable(); // parcel, document
            $table->integer('delivery_courier_rate')->nullable(); // price dari courier
            $table->integer('delivery_shipping_fee')->nullable();
            $table->integer('delivery_shipping_fee_discount')->default(0);
            $table->integer('delivery_shipping_fee_surcharge')->default(0);
            $table->text('delivery_courier_history')->nullable(); // JSON tracking history

            // pickup method options
            $table->boolean('is_pickup_courier')->default(false); // apakah menggunakan kurir untuk pickup (gojek/grab)
            $table->boolean('is_pickup_free')->default(false); // apakah pickup gratis oleh toko sendiri
            $table->string('pickup_method', 50)->nullable(); // gojek, grab, free_pickup, self_dropoff

            // delivery method options
            $table->boolean('is_delivery_courier')->default(false); // apakah menggunakan kurir untuk delivery (gojek/grab)
            $table->boolean('is_delivery_free')->default(false); // apakah delivery gratis oleh toko sendiri
            $table->string('delivery_method', 50)->nullable(); // gojek, grab, free_delivery, self_pickup

            // staff assignment untuk pickup/delivery free oleh toko
            $table->foreignId('pickup_staff_id')->nullable()->constrained('users')->nullOnDelete(); // staff toko yang jemput
            $table->foreignId('delivery_staff_id')->nullable()->constrained('users')->nullOnDelete(); // staff toko yang antar

            $table->string('photo_before')->nullable();
            $table->string('photo_after')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
