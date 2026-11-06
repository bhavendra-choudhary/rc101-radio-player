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
        if (!Schema::hasTable('community_events')) {
            Schema::create('community_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description');
                $table->string('image_path')->nullable();
                $table->string('icon')->default('fas fa-calendar-check'); // Font Awesome icon class
                $table->string('link_url')->nullable();
                $table->string('link_text')->default('Learn More');
                $table->date('event_date')->nullable();
                $table->time('event_time')->nullable();
                $table->string('location')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        } else {
            Schema::table('community_events', function (Blueprint $table) {
                if (!Schema::hasColumn('community_events', 'image_path')) {
                    $table->string('image_path')->nullable();
                }
                if (!Schema::hasColumn('community_events', 'icon')) {
                    $table->string('icon')->default('fas fa-calendar-check');
                }
                if (!Schema::hasColumn('community_events', 'link_url')) {
                    $table->string('link_url')->nullable();
                }
                if (!Schema::hasColumn('community_events', 'link_text')) {
                    $table->string('link_text')->default('Learn More');
                }
                if (!Schema::hasColumn('community_events', 'event_time')) {
                    $table->time('event_time')->nullable();
                }
                if (!Schema::hasColumn('community_events', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_events');
    }
};
