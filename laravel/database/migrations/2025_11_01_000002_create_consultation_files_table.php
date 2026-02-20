<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consultation_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('file_type', 50);
            $table->integer('version')->default(1);
            $table->unsignedBigInteger('parent_file_id')->nullable()->comment('For file versioning');
            $table->unsignedBigInteger('uploaded_by');
            $table->text('version_notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('parent_file_id')->references('id')->on('consultation_files')->onDelete('set null');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('consultation_id');
            $table->index('parent_file_id');
            $table->index(['consultation_id', 'file_name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consultation_files');
    }
}

