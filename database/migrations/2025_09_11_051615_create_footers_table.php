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
        Schema::create('footers', function (Blueprint $table) {
            $table->id();
            
            // Company Info Section
            $table->string('brand_name')->default('Radio Station');
            //$table->text('description')->default('Your favorite source for music, news, and entertainment. Tune in 24/7 for the best experience.');
            $table->text('description')->nullable();
            
            // Quick Links Section
            $table->string('home_link_text')->default('Home');
            $table->string('home_link_url')->default('#');
            $table->string('news_link_text')->default('News');
            $table->string('news_link_url')->default('#news');
            $table->string('concerts_link_text')->default('Concerts');
            $table->string('concerts_link_url')->default('#concerts');
            $table->string('events_link_text')->default('Events');
            $table->string('events_link_url')->default('#events');
            $table->string('contact_link_text')->default('Contact');
            $table->string('contact_link_url')->default('#contact');
            
            // Contact Info Section
            $table->string('address')->default('123 Radio Street, City, Country');
            $table->string('phone')->default('+1 (123) 456-7890');
            $table->string('email')->default('info@radiostation.com');
            $table->string('frequency')->default('98.5 FM');
            
            // Social Media Links
            $table->string('facebook_url')->default('#');
            $table->string('instagram_url')->default('#');
            $table->string('twitter_url')->default('#');
            $table->string('youtube_url')->default('#');
            
            // Copyright
            $table->string('copyright_text')->default('2023 Radio Station. All Rights Reserved.');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footers');
    }
};
