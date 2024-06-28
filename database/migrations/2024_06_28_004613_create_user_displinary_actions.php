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
        Schema::create('user_disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('disciplinary_action')->nullable()->default(null);
            $table->foreignId('disciplinary_action_taken_by')->nullable()->default(null);
            $table->text("reason")->nullable()->default(null);
            $table->dateTime("user_read_at")->nullable()->default(null);
            $table->string("status")->nullable()->default(null)->comment('opened/closed'); //
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
        Schema::dropIfExists('user_disciplinary_records');
    }
};
