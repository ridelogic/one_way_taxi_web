<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website bookings are saved in the fleet MongoDB as enquiries (enquiry_logs) first; the dispatcher moves them to
 * Manage Trips. fleet_enquiry_id = enquiry_logs _id; fleet_trip_id = passengers_logs _id once moved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('fleet_enquiry_id')->nullable()->index()->after('fleet_model_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['fleet_enquiry_id']);
            $table->dropColumn('fleet_enquiry_id');
        });
    }
};
