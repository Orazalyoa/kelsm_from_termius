<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consultations', function (Blueprint $table) {
            // 添加新的时间戳字段
            $table->timestamp('lawyer_delivered_at')->nullable()->after('delivered_at')->comment('律师提交交付时间');
            $table->timestamp('client_confirmed_at')->nullable()->after('lawyer_delivered_at')->comment('客户确认交付时间');
        });

        // 更新 status 枚举类型，添加新状态
        DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('pending', 'assigned', 'in_progress', 'delivered', 'awaiting_completion', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['lawyer_delivered_at', 'client_confirmed_at']);
        });

        // 恢复原来的 status 枚举
        DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};

