<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('topic_type', ['legal_consultation', 'contracts_deals', 'legal_services', 'other'])
                ->default('legal_consultation');
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])
                ->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_lawyer_id')->nullable();
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_lawyer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('set null');

            // Indexes
            $table->index('status');
            $table->index('topic_type');
            $table->index('created_by');
            $table->index('assigned_lawyer_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consultations');
    }
}

