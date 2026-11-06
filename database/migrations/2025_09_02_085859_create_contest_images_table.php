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
        Schema::create('contest_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contest_images');
    }
};
