<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationAnalysesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consultation_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id')->index();
            $table->unsignedBigInteger('analyzed_by')->nullable();
            $table->text('summary')->nullable();
            $table->json('key_points')->nullable();
            $table->string('suggested_priority', 50)->nullable();
            $table->string('suggested_category', 100)->nullable();
            $table->json('raw_analysis')->nullable();
            $table->timestamps();
            
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('analyzed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consultation_analyses');
    }
}
