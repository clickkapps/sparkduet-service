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
        Schema::create('chat_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId("chat_message_id")->nullable()->default(null)->comment('last chat message id');
            $table->dateTime('matched_at')->nullable()->default(null)->comment('matched_at will be send when opponent replies to the message');
            $table->dateTime('read_first_impression_note_at')->nullable()->default(null)->comment('matched_at will be send when opponent replies to the message');
            $table->dateTime('deleted_at')->nullable()->default(null);
            $table->foreignId('created_by')->constrained('users');
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
        Schema::dropIfExists('chat_connections');
    }
};
