<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page targeting for promotional announcements (homepage and/or the location pages). Additive only:
 *  - show_on_home      homepage yes/no — default true, so existing announcements keep showing where they did
 *  - location_scope    'none' (no location pages, the default for existing rows) | 'all' | 'selected'
 *  - district_page_promo_announcement   the pages chosen for 'selected' (same pivot pattern as promo videos)
 * No existing column or record is changed or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_announcements', function (Blueprint $table) {
            $table->boolean('show_on_home')->default(true)->after('ends_at');
            $table->string('location_scope', 10)->default('none')->after('show_on_home');
        });

        Schema::create('district_page_promo_announcement', function (Blueprint $table) {
            $table->foreignId('district_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promo_announcement_id')->constrained()->cascadeOnDelete();
            $table->primary(['district_page_id', 'promo_announcement_id']);
            $table->index('promo_announcement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_page_promo_announcement');
        Schema::table('promo_announcements', function (Blueprint $table) {
            $table->dropColumn(['show_on_home', 'location_scope']);
        });
    }
};
