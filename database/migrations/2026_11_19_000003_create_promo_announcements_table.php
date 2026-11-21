<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promotional announcements: the scrolling offers ticker below the homepage header (Admin → Promotional
 * Announcements). New table only — no existing table or record is changed. Announcements are disabled by default
 * and appear only while enabled and inside their optional start/end window.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('message', 255);
            $table->string('link_url', 500)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->index(['is_enabled', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_announcements');
    }
};
