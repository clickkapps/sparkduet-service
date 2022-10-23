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
        Schema::create('email_authorizations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->default(null);
            $table->string('code')->nullable()->default(null);
            $table->string('status')->nullable()->default(null)->comment('opened / closed');
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
        Schema::dropIfExists('email_authorizations');
    }
};
