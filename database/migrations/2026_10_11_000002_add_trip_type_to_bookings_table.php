<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Booking tabs (One Way / Outstation / Rental). The quick homepage widget asks only for
     * route, date, mobile and email, so name and pickup time become optional.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('trip_type', 20)->default('one_way')->after('reference')->index();
            $table->string('rental_package', 60)->nullable()->after('trip_type');
            $table->date('return_date')->nullable()->after('travel_time');
            $table->string('customer_name', 120)->nullable()->change();
            $table->time('travel_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['trip_type']);
            $table->dropColumn(['trip_type', 'rental_package', 'return_date']);
        });
    }
};
