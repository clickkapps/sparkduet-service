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
        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('min_age')->nullable()->default(null);
            $table->string('max_age')->nullable()->default(null);
            $table->string('gender')->nullable()->default(null);
            $table->string('countries_option')->nullable()->default(null)->comment('all / only / except');
            $table->string('countries_filtered')->nullable()->default(null)->comment('GH / US / etc');
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
        Schema::dropIfExists('filters');
    }
};
