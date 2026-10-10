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
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_inside_polygon')->default(false)->after('check_in_distance');
            $table->string('location_verification_status')->nullable()->after('is_inside_polygon');
            $table->boolean('liveness_verified')->default(false)->after('location_verification_status');
            $table->string('liveness_method')->nullable()->after('liveness_verified');
            $table->timestamp('liveness_verified_at')->nullable()->after('liveness_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'is_inside_polygon',
                'location_verification_status',
                'liveness_verified',
                'liveness_method',
                'liveness_verified_at',
            ]);
        });
    }
};
