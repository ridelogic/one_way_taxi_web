<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Result of YouTube's own embed check (oEmbed) for each promo video, so the admin can see which videos cannot be
 * played on the website and the site can show a "Watch on YouTube" card instead of an error player.
 * ok = public and embeddable · blocked = the owner disabled embedding · unavailable = private, deleted or not found ·
 * null = not checked yet (existing records; checked on the next save).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_videos', function (Blueprint $table) {
            $table->string('embed_status', 20)->nullable()->after('youtube_id');
            $table->timestamp('embed_checked_at')->nullable()->after('embed_status');
        });
    }

    public function down(): void
    {
        Schema::table('promo_videos', fn (Blueprint $table) => $table->dropColumn(['embed_status', 'embed_checked_at']));
    }
};
