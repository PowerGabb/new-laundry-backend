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
        Schema::create('laundry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('laundry_categories')->cascadeOnDelete();
            $table->string('name'); // e.g., "Cuci Setrika Regular", "Jas", "Selimut"
            $table->string('slug'); // e.g., "cuci-setrika-regular", "jas"
            $table->text('description')->nullable();
            $table->enum('unit', ['kg', 'pcs']); // satuan: kilogram atau pieces
            $table->integer('price'); // harga per unit
            $table->integer('min_quantity')->default(1); // minimal order
            $table->integer('estimated_duration_hours')->default(24); // estimasi selesai (jam)
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundry_items');
    }
};
