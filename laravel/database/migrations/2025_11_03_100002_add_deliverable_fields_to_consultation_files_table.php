<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliverableFieldsToConsultationFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consultation_files', function (Blueprint $table) {
            // 文件分类（在file_type之后）
            $table->enum('file_category', ['attachment', 'deliverable', 'supplement'])
                ->default('attachment')
                ->after('file_type')
                ->comment('文件分类：attachment-初始附件，deliverable-交付物，supplement-补充材料');
            
            // 交付物控制（在file_category之后）
            $table->boolean('is_deliverable')->default(false)->after('file_category')->comment('是否为交付物');
            $table->timestamp('delivered_at')->nullable()->after('is_deliverable')->comment('交付时间');
            $table->boolean('can_client_access')->default(true)->after('delivered_at')->comment('客户是否可访问');
            
            // 索引
            $table->index(['consultation_id', 'is_deliverable']);
            $table->index('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultation_files', function (Blueprint $table) {
            // 删除索引
            $table->dropIndex(['consultation_id', 'is_deliverable']);
            $table->dropIndex(['delivered_at']);
            
            // 删除字段
            $table->dropColumn([
                'file_category',
                'is_deliverable',
                'delivered_at',
                'can_client_access',
            ]);
        });
    }
}

