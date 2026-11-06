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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'device_token')) {
                $table->string('device_token', 80)->nullable()->index()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('device_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'device_token')) {
                $table->dropColumn('device_token');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
