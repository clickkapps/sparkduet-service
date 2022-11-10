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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('targeted_gender')->nullable()->default(null);
            $table->string('targeted_min_age')->nullable()->default(null);
            $table->string('targeted_max_age')->nullable()->default(null);
            $table->text('description')->nullable()->default(null);
            $table->boolean('comments_enabled')->nullable()->default(false);
            $table->integer('total_comments')->nullable()->default(0);
            $table->dateTime('feed_expiry_date')->nullable()->default(null)->comment("when a story expires it won't show in feeds, but will be on user profile ");
            $table->string('targeted_countries_option')->nullable()->default('all')->comment('all / only / except');
            $table->boolean('blocked')->nullable()->default(false);
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
        Schema::dropIfExists('stories');
    }
};
