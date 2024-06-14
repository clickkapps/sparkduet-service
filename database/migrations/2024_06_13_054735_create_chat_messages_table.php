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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_connection_id')->constrained('chat_connections');
            $table->string('client_id')->nullable()->nullable();
            $table->foreignId('parent_id')->nullable()->default(null);
            $table->dateTime('deleted_at')->nullable()->default(false);
            $table->dateTime('delivered_at')->nullable()->default(false);
            $table->dateTime('seen_at')->nullable()->default(false);
            $table->string('attachment_path')->nullable()->default(null);
            $table->string('attachment_type')->nullable()->default(null)->comment('image/video');
            $table->text('text')->nullable()->default(null);
            $table->foreignId('sent_by_id')->constrained('users');
            $table->foreignId('sent_to_id')->constrained('users');
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
        Schema::dropIfExists('chat_messages');
    }
};
