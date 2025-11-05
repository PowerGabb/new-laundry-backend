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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('detail_address', 255);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone', 20)->nullable();
            $table->tinyInteger('working_hours')->default(10);
            $table->integer('price_per_kg')->default(5000);
            $table->string('image_url')->nullable();
            $table->boolean('pickup_gojek')->default(false);
            $table->boolean('pickup_grab')->default(false);
            $table->boolean('pickup_free')->default(false);
            $table->json('pickup_free_schedule')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
