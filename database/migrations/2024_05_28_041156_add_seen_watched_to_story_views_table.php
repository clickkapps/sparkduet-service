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
        Schema::table('story_views', function (Blueprint $table) {
            $table->dateTime('seen_at')->nullable()->default(null);
            $table->dateTime('watched_created_at')->nullable()->default(null);
            $table->dateTime('watched_updated_at')->nullable()->default(null);
            $table->integer('watched_count')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('story_views', function (Blueprint $table) {
            $table->dropColumn(['seen_at', 'watched_created_at', 'watched_updated_at', 'watched_count']);
        });
    }
};
