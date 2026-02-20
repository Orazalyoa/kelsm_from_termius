<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE invite_codes MODIFY COLUMN user_type ENUM('expert', 'company_admin', 'operator') DEFAULT 'expert'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE invite_codes MODIFY COLUMN user_type ENUM('expert', 'company_admin') DEFAULT 'expert'");
    }
};


