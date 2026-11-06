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
        Schema::create('apis', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., "news_api", "bandsintown_api"
            $table->text('value'); // API key or endpoint URL
            $table->string('type')->default('api_key'); // api_key, endpoint, token, etc.
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // Enable/disable API
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apis');
    }
};
