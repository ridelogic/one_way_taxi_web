<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('destination_city_id')->constrained('cities')->restrictOnDelete();
            $table->string('slug', 200)->unique();
            $table->decimal('distance_km', 8, 1);
            $table->unsignedInteger('duration_minutes');
            $table->decimal('base_fare', 10, 2); // "Starting fare" shown publicly
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('short_description', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_sample_fare')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['origin_city_id', 'destination_city_id']);
            $table->index(['is_active', 'is_popular', 'sort_order']);
        });

        Schema::create('route_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxi_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('fare', 10, 2);
            $table->timestamps();

            $table->unique(['taxi_route_id', 'vehicle_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_fares');
        Schema::dropIfExists('taxi_routes');
    }
};
