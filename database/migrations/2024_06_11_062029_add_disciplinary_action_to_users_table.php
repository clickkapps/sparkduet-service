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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('blocked');
            $table->string('disciplinary_action')->nullable()->default(null)->comment('warned/banned/etc');
            $table->dateTime('disciplinary_action_taken_at')->nullable()->default(null);
            $table->foreignId('disciplinary_action_taken_by')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('blocked')->nullable()->default(false);
            $table->dropColumn([
                'disciplinary_action',
                'disciplinary_action_taken_at',
                'disciplinary_action_taken_by'
            ]);
        });
    }
};
