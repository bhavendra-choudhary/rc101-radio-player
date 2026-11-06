<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('footers', function (Blueprint $table) {
            $table->json('seo_settings')
                ->nullable()
                ->after('theme_settings'); // adjust if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('footers', function (Blueprint $table) {
            $table->dropColumn('seo_settings');
        });
    }
};
