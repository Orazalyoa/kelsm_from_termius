<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ConvertUsersTableToInnodb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 将 users 表从 MyISAM 转换为 InnoDB
        // 这是为了支持聊天系统表的外键约束
        DB::statement('ALTER TABLE users ENGINE=InnoDB');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 回滚时转换回 MyISAM（通常不需要）
        DB::statement('ALTER TABLE users ENGINE=MyISAM');
    }
}
