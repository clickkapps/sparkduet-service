<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->boolean("enable_chat_notifications")->nullable()->default(true);
            $table->boolean("enable_profile_views_notifications")->nullable()->default(true);
            $table->boolean("enable_story_views_notifications")->nullable()->default(true);
            $table->string("theme_appearance")->nullable()->default("light")->comment("light/dark");
            $table->string("font_family")->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_settings');
    }
};
