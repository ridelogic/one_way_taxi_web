<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fleet backend (MongoDB) integration:
 *  - vehicle_categories.fleet_model_id links a website vehicle to a fleet motor_model (_id) so it can be priced
 *    with the admin's fare rules;
 *  - bookings keep the coordinates, verified route and the fleet trip id (passengers_logs _id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_categories', function (Blueprint $table) {
            $table->unsignedInteger('fleet_model_id')->nullable()->after('passenger_capacity');
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Full Google Places addresses are longer than city names.
            $table->string('pickup_location', 255)->nullable()->change();
            $table->string('drop_location', 255)->nullable()->change();
            $table->decimal('pickup_lat', 10, 7)->nullable()->after('drop_address');
            $table->decimal('pickup_lng', 10, 7)->nullable()->after('pickup_lat');
            $table->decimal('drop_lat', 10, 7)->nullable()->after('pickup_lng');
            $table->decimal('drop_lng', 10, 7)->nullable()->after('drop_lat');
            $table->decimal('distance_km', 8, 2)->nullable()->after('drop_lng');
            $table->unsignedInteger('duration_sec')->nullable()->after('distance_km');
            $table->unsignedInteger('fleet_model_id')->nullable()->after('vehicle_category_id');
            $table->unsignedBigInteger('fleet_trip_id')->nullable()->index()->after('fleet_model_id');
            $table->timestamp('fleet_synced_at')->nullable()->after('fleet_trip_id');
            $table->string('fleet_sync_error', 500)->nullable()->after('fleet_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['fleet_trip_id']);
            $table->dropColumn(['pickup_lat', 'pickup_lng', 'drop_lat', 'drop_lng', 'distance_km', 'duration_sec', 'fleet_model_id', 'fleet_trip_id', 'fleet_synced_at', 'fleet_sync_error']);
        });

        Schema::table('vehicle_categories', function (Blueprint $table) {
            $table->dropColumn('fleet_model_id');
        });
    }
};
