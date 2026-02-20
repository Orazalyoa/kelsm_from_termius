<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveRevokedStatusFromInviteCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete all revoked invite codes (we don't need them anymore)
        DB::table('invite_codes')->where('status', 'revoked')->delete();
        
        // Modify status enum to remove 'revoked'
        DB::statement("ALTER TABLE invite_codes MODIFY COLUMN status ENUM('active', 'expired') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Add back 'revoked' status
        DB::statement("ALTER TABLE invite_codes MODIFY COLUMN status ENUM('active', 'expired', 'revoked') DEFAULT 'active'");
    }
}
