<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInviteCodeUsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invite_code_uses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invite_code_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('used_at');
            $table->timestamps();
            
            $table->index(['invite_code_id', 'user_id']);
            $table->foreign('invite_code_id')->references('id')->on('invite_codes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invite_code_uses');
    }
}
