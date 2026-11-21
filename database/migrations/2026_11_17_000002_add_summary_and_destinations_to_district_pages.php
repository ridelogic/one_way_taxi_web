<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** District cards (homepage grid) need a short description; district pages list popular destinations. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('district_pages', function (Blueprint $table) {
            $table->string('summary', 200)->nullable()->after('intro');
            $table->json('popular_destinations')->nullable()->after('pickup_locations');
        });
    }

    public function down(): void
    {
        Schema::table('district_pages', function (Blueprint $table) {
            $table->dropColumn(['summary', 'popular_destinations']);
        });
    }
};
