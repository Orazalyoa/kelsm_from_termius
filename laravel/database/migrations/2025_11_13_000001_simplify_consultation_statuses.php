<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SimplifyConsultationStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Add 'archived' to the ENUM (keep old values temporarily)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('pending', 'assigned', 'in_progress', 'delivered', 'awaiting_completion', 'completed', 'archived', 'cancelled') NOT NULL DEFAULT 'pending'");
        }

        // Step 2: Migrate existing data to new statuses
        DB::table('consultations')
            ->where('status', 'assigned')
            ->update(['status' => 'in_progress']);
        
        DB::table('consultations')
            ->whereIn('status', ['delivered', 'awaiting_completion', 'completed'])
            ->update(['status' => 'archived']);

        // Step 3: Add archived_at and archived_by fields
        Schema::table('consultations', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('completed_at');
            $table->unsignedBigInteger('archived_by')->nullable()->after('archived_at');
            
            // Add foreign key for archived_by
            $table->foreign('archived_by')->references('id')->on('users')->onDelete('set null');
        });

        // Step 4: Set archived_at for newly migrated archived consultations
        DB::table('consultations')
            ->where('status', 'archived')
            ->whereNotNull('completed_at')
            ->update(['archived_at' => DB::raw('completed_at')]);

        // Step 5: Remove old ENUM values, keep only the 4 new statuses
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('pending', 'in_progress', 'archived', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert status changes
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE consultations MODIFY COLUMN status ENUM('pending', 'assigned', 'in_progress', 'delivered', 'awaiting_completion', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }

        // Revert data migrations (approximate, not exact)
        DB::table('consultations')
            ->where('status', 'archived')
            ->update(['status' => 'completed']);

        // Remove new fields
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['archived_by']);
            $table->dropColumn(['archived_at', 'archived_by']);
        });
    }
}

