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
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->text('description')->nullable()->default(null);
            $table->boolean('comments_enabled')->nullable()->default(false);
            $table->dateTime('expiry_date')->nullable()->default(null)->comment("when a story expires it won't show in feeds, but will be on user profile ");
            $table->string('targeted_countries_option')->nullable()->default('all')->comment('all / only / except');
            $table->boolean('blocked_by_admin')->nullable()->default(false);
            $table->string('media_path');
            $table->string('media_type');
            $table->string('city')->nullable()->default(null);
            $table->string('country')->nullable()->default(null);
            $table->string('region')->nullable()->default(null);
            $table->string('loc')->nullable()->default(null);
            $table->string('timezone')->nullable()->default(null);
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
