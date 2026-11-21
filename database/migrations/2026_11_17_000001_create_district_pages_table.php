<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * District landing pages (/tamil-nadu/{slug}): the editorial content for each district.
 * Routes, fares and vehicles are NOT duplicated here — a district is linked to the existing cities
 * (city_district_page), and its routes and fares are read from taxi_routes / vehicle_categories / fare rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_pages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('state', 100)->default('Tamil Nadu');
            $table->string('headquarters', 100)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('heading', 160)->nullable();
            $table->text('intro')->nullable();
            $table->longText('content')->nullable();
            $table->json('pickup_locations')->nullable();
            $table->string('nearest_airport', 160)->nullable();
            $table->json('related_districts')->nullable();
            $table->foreignId('hero_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 255)->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('city_district_page', function (Blueprint $table) {
            $table->foreignId('district_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->primary(['district_page_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_district_page');
        Schema::dropIfExists('district_pages');
    }
};
