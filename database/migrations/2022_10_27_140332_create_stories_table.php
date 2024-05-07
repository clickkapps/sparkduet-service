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
            $table->boolean('comments_disabled_at')->nullable()->default(null);
            $table->dateTime('blocked_by_admin_at')->nullable()->default(null);
            $table->string('media_path');
            $table->string('media_type')->comment("image/video");
            $table->string('purpose')->nullable()->default(null)->comment("introduction/previousRelationship/expectations/career");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
