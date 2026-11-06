<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('footers', function (Blueprint $table) {
            $table->json('theme_settings')->nullable()->after('youtube_url');
        });
    }

    public function down()
    {
        Schema::table('footers', function (Blueprint $table) {
            $table->dropColumn('theme_settings');
        });
    }
};