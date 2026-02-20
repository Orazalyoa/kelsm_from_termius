<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationLawyersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consultation_lawyers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->unsignedBigInteger('lawyer_id');
            $table->boolean('is_primary')->default(false)->comment('是否为主要负责律师');
            $table->unsignedBigInteger('assigned_by')->nullable()->comment('分配人ID');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('lawyer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('consultation_id');
            $table->index('lawyer_id');
            $table->index('assigned_at');
            
            // Unique constraint: 同一个咨询不能重复分配给同一个律师
            $table->unique(['consultation_id', 'lawyer_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consultation_lawyers');
    }
}

