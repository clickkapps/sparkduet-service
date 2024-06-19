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
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_id')->constrained('users', 'id');
            $table->foreignId('profile_id')->constrained('users', 'id');
            $table->dateTime("profile_owner_notified_at")->nullable()->default(null)->comment('This tells if profile owner knows have seen people who viewed profile');
            $table->dateTime("profile_owner_read_at")->nullable()->default(null)->comment('This tells if profile owner knows have seen people who viewed profile');
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
        Schema::dropIfExists('profile_views');
    }
};
