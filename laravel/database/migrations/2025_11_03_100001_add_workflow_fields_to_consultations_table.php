<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkflowFieldsToConsultationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consultations', function (Blueprint $table) {
            // 编号（在id之后）
            $table->string('reference_number', 50)->unique()->nullable()->after('id');
            
            // 完成信息（在description之后）
            $table->text('resolution')->nullable()->after('description')->comment('咨询结论/解决方案');
            $table->text('completion_notes')->nullable()->after('resolution')->comment('完成备注');
            
            // 优先级追踪（在priority之后）
            $table->timestamp('priority_escalated_at')->nullable()->after('priority')->comment('优先级提升时间');
            
            // 时间追踪（在assigned_at之后）
            $table->timestamp('started_at')->nullable()->after('assigned_at')->comment('律师开始工作时间');
            
            // 交付时间（在completed_at之后）
            $table->timestamp('delivered_at')->nullable()->after('completed_at')->comment('交付时间');
            $table->timestamp('last_activity_at')->nullable()->after('delivered_at')->comment('最后活动时间');
            
            // 取消/撤回（在last_activity_at之后）
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('last_activity_at')->comment('谁取消的');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by')->comment('取消/撤回原因');
            
            // 外键
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            
            // 索引
            $table->index('reference_number');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultations', function (Blueprint $table) {
            // 删除外键
            $table->dropForeign(['cancelled_by']);
            
            // 删除索引
            $table->dropIndex(['reference_number']);
            $table->dropIndex(['last_activity_at']);
            
            // 删除字段
            $table->dropColumn([
                'reference_number',
                'resolution',
                'completion_notes',
                'priority_escalated_at',
                'started_at',
                'delivered_at',
                'last_activity_at',
                'cancelled_by',
                'cancellation_reason',
            ]);
        });
    }
}

