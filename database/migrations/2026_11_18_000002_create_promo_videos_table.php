<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video promotions: YouTube videos (embedded, never uploaded) shown on the homepage and/or district pages.
 * One record per video; a many-to-many pivot assigns it to any number of districts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('description', 500)->nullable();
            $table->string('youtube_url', 255);
            $table->string('youtube_id', 20)->index();
            $table->foreignId('thumbnail_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('category', 40)->default('promotional');
            $table->boolean('show_on_home')->default(false);
            $table->boolean('show_on_districts')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'show_on_home', 'sort_order']);
        });

        Schema::create('district_page_promo_video', function (Blueprint $table) {
            $table->foreignId('district_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promo_video_id')->constrained()->cascadeOnDelete();
            $table->primary(['district_page_id', 'promo_video_id']);
            $table->index('promo_video_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_page_promo_video');
        Schema::dropIfExists('promo_videos');
    }
};
