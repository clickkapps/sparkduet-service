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
        Schema::table('user_infos', function (Blueprint $table) {
            $table->string("preferred_gender")->nullable()->default(null);
            $table->integer("preferred_min_age")->nullable()->default(null);
            $table->integer("preferred_max_age")->nullable()->default(null);
            $table->text("preferred_races")->nullable()->default(null);
            $table->text("preferred_nationalities")->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_infos', function (Blueprint $table) {
            $table->dropColumn(['preferred_gender', 'preferred_min_age', 'preferred_max_age', 'preferred_races', "preferred_nationalities"]);
        });
    }
};
