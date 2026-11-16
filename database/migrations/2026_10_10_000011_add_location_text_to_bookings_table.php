<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Customers type their pickup/destination; the text is kept even when it doesn't match a configured city. */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('pickup_location', 120)->nullable()->after('destination_city_id');
            $table->string('drop_location', 120)->nullable()->after('pickup_location');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_location', 'drop_location']);
        });
    }
};
