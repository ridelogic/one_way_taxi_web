<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * District landing pages: drop locations, curated routes, FAQs, per-district service descriptions,
 * a separate card image, image alt text and full SEO / Open Graph control. Lists are JSON columns on the
 * district row (like the existing pickup_locations), so they are edited together with the page in Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('district_pages', function (Blueprint $table) {
            $table->json('drop_locations')->nullable()->after('pickup_locations');
            $table->json('popular_routes')->nullable()->after('popular_destinations');
            $table->json('faqs')->nullable()->after('popular_routes');
            $table->json('service_texts')->nullable()->after('faqs');
            $table->foreignId('card_media_id')->nullable()->after('hero_media_id')->constrained('media_assets')->nullOnDelete();
            $table->string('image_alt', 160)->nullable()->after('card_media_id');
            $table->string('seo_keywords', 255)->nullable()->after('seo_description');
            $table->string('canonical_url', 255)->nullable()->after('seo_keywords');
            $table->string('og_title', 160)->nullable()->after('canonical_url');
            $table->string('og_description', 255)->nullable()->after('og_title');
            $table->foreignId('og_media_id')->nullable()->after('og_description')->constrained('media_assets')->nullOnDelete();
            $table->boolean('content_reviewed')->default(false)->after('is_published');
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('district_pages', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'sort_order']);
            $table->dropConstrainedForeignId('card_media_id');
            $table->dropConstrainedForeignId('og_media_id');
            $table->dropColumn(['drop_locations', 'popular_routes', 'faqs', 'service_texts', 'image_alt', 'seo_keywords', 'canonical_url', 'og_title', 'og_description', 'content_reviewed']);
        });
    }
};
