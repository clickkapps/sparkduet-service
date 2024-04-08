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
        Schema::create('user_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->text('bio')->nullable()->default("Hey, I am on the lookout for a partner. Interested in exploring the journey of finding love together?");
            $table->dateTime('dob')->nullable()->default(null);
            $table->integer('age')->nullable()->default(null);
            $table->string('gender')->nullable()->default(null);
            $table->string('city')->nullable()->default(null);
            $table->string('country')->nullable()->default(null);
            $table->string('region')->nullable()->default(null);
            $table->string('loc')->nullable()->default(null);
            $table->string('timezone')->nullable()->default(null);
            $table->string('profile_pic_path')->nullable()->default(null);
            $table->string('introductory_video_path')->nullable()->default(null);
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
        Schema::dropIfExists('user_info');
    }
};
