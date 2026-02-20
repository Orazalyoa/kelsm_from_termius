<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPermissionsToInviteCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, modify the user_type enum to include company_admin
        DB::statement("ALTER TABLE invite_codes MODIFY COLUMN user_type ENUM('expert', 'company_admin') DEFAULT 'expert'");
        
        Schema::table('invite_codes', function (Blueprint $table) {
            // Add permissions field as JSON
            $table->json('permissions')->nullable()->after('user_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invite_codes', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
        
        // Revert user_type enum to original
        DB::statement("ALTER TABLE invite_codes MODIFY COLUMN user_type ENUM('expert') DEFAULT 'expert'");
    }
}
