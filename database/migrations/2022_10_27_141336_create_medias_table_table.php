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
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->nullable()->default(null);
            $table->foreignId('user_id')->nullable()->default(null);
            $table->foreignId('chat_id')->nullable()->default(null);
            $table->string('source')->nullable()->default(null)->comment('stories, chats, user_profile_pic');
            $table->string('path')->nullable()->default(null);
            $table->string('type')->nullable()->default(null)->comment('video/mp3, image/jpeg');
            $table->string('size')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->string('color_filter')->nullable()->default(null);
            $table->string('background_music')->nullable()->default(null);
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
        Schema::dropIfExists('medias_table');
    }
};
