<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('category', 50)->nullable(); // Sedan, SUV, MUV ...
            $table->string('example_models')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedTinyInteger('passenger_capacity')->default(4);
            $table->unsignedTinyInteger('luggage_capacity')->default(2);
            $table->string('short_description', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('fare_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_category_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->decimal('per_km_rate', 8, 2);
            $table->unsignedInteger('minimum_km')->default(0);
            $table->decimal('minimum_fare', 10, 2)->default(0);
            $table->decimal('driver_allowance', 10, 2)->default(0);
            $table->json('additional_charges')->nullable(); // [{label, amount|null, note}]
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_rules');
        Schema::dropIfExists('vehicle_categories');
    }
};
