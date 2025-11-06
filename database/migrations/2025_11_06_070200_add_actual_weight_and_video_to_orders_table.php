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
        Schema::table('orders', function (Blueprint $table) {
            // Actual items setelah ditimbang/dihitung laundry
            // Format: [{"item_id": 1, "item_name": "Cuci Setrika Regular", "quantity": 5, "unit": "kg", "price_per_unit": 7000, "subtotal": 35000}]
            $table->json('actual_weight_items')->nullable()->after('items_detail');

            // Actual total amount berdasarkan actual weight/count
            $table->integer('actual_total_amount')->nullable()->after('total_amount');

            // Video proof penimbangan/penghitungan
            $table->string('proof_video_url')->nullable()->after('photo_after');

            // Timestamp when actual weight was recorded
            $table->timestamp('actual_weight_recorded_at')->nullable()->after('processing_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'actual_weight_items',
                'actual_total_amount',
                'proof_video_url',
                'actual_weight_recorded_at',
            ]);
        });
    }
};
