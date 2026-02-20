<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Menu;

class UpdateConsultationMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder updates the consultation menu to reflect the new simplified status system.
     * Run this after migrating to the new 4-status system.
     *
     * @return void
     */
    public function run()
    {
        // Delete old menu items that no longer exist
        Menu::where('uri', 'consultations?status=assigned')->delete();
        Menu::where('uri', 'consultations?status=delivered')->delete();
        Menu::where('uri', 'consultations?status=awaiting_completion')->delete();
        Menu::where('uri', 'consultations?status=completed')->delete();

        // Find the Consultation Management parent menu
        $consultationMenu = Menu::where('title', 'Consultations')
            ->orWhere('title', 'Консультации')
            ->orWhere('title', 'Кеңестер')
            ->where('parent_id', 0)
            ->first();

        if (!$consultationMenu) {
            $this->command->warn('Consultation parent menu not found. Skipping menu update.');
            return;
        }

        // Ensure the archived menu exists
        Menu::firstOrCreate([
            'title' => 'Archived',
            'uri' => 'consultations?status=archived',
            'parent_id' => $consultationMenu->id,
        ], [
            'icon' => '',
            'order' => 4,
        ]);

        // Update order for remaining items
        $pendingMenu = Menu::where('uri', 'consultations?status=pending')
            ->where('parent_id', $consultationMenu->id)
            ->first();
        if ($pendingMenu) {
            $pendingMenu->update(['order' => 2]);
        }

        $inProgressMenu = Menu::where('uri', 'consultations?status=in_progress')
            ->where('parent_id', $consultationMenu->id)
            ->first();
        if ($inProgressMenu) {
            $inProgressMenu->update(['order' => 3]);
        }

        $this->command->info('Consultation menu updated successfully!');
        $this->command->info('Removed: Assigned, Completed status menus');
        $this->command->info('Added: Archived status menu');
    }
}

