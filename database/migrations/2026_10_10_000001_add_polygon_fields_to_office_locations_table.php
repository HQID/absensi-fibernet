<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('office_locations', function (Blueprint $table) {
            $table->json('polygon_coordinates')->nullable()->after('name');
            $table->decimal('center_latitude', 10, 8)->nullable()->after('polygon_coordinates');
            $table->decimal('center_longitude', 11, 8)->nullable()->after('center_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_locations', function (Blueprint $table) {
            $table->dropColumn(['polygon_coordinates', 'center_latitude', 'center_longitude']);
        });
    }
};
