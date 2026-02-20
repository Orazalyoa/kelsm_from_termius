<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateConsultationAdminMenus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 删除旧的菜单项
        DB::table('admin_menu')
            ->where('uri', 'consultations?status=assigned')
            ->delete();
        
        DB::table('admin_menu')
            ->where('uri', 'consultations?status=delivered')
            ->delete();
        
        DB::table('admin_menu')
            ->where('uri', 'consultations?status=awaiting_completion')
            ->delete();
        
        DB::table('admin_menu')
            ->where('uri', 'consultations?status=completed')
            ->delete();

        // 查找咨询管理父菜单
        $consultationParent = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where(function ($query) {
                $query->where('title', 'Consultations')
                      ->orWhere('title', 'Консультации')
                      ->orWhere('title', 'Кеңестер')
                      ->orWhere('title', '咨询管理')
                      ->orWhere('title', '咨询');
            })
            ->first();

        if ($consultationParent) {
            // 检查是否已存在 archived 菜单
            $archivedExists = DB::table('admin_menu')
                ->where('uri', 'consultations?status=archived')
                ->where('parent_id', $consultationParent->id)
                ->exists();

            if (!$archivedExists) {
                // 添加新的 Archived 菜单项
                DB::table('admin_menu')->insert([
                    'parent_id' => $consultationParent->id,
                    'order' => 4,
                    'title' => 'Archived',
                    'icon' => '',
                    'uri' => 'consultations?status=archived',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 更新现有菜单项的顺序
            DB::table('admin_menu')
                ->where('uri', 'consultations')
                ->where('parent_id', $consultationParent->id)
                ->update(['order' => 1]);

            DB::table('admin_menu')
                ->where('uri', 'consultations?status=pending')
                ->where('parent_id', $consultationParent->id)
                ->update(['order' => 2]);

            DB::table('admin_menu')
                ->where('uri', 'consultations?status=in_progress')
                ->where('parent_id', $consultationParent->id)
                ->update(['order' => 3]);

            DB::table('admin_menu')
                ->where('uri', 'consultations?status=archived')
                ->where('parent_id', $consultationParent->id)
                ->update(['order' => 4]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 删除 archived 菜单
        DB::table('admin_menu')
            ->where('uri', 'consultations?status=archived')
            ->delete();

        // 查找咨询管理父菜单
        $consultationParent = DB::table('admin_menu')
            ->where('parent_id', 0)
            ->where(function ($query) {
                $query->where('title', 'Consultations')
                      ->orWhere('title', 'Консультации')
                      ->orWhere('title', 'Кеңестер')
                      ->orWhere('title', '咨询管理')
                      ->orWhere('title', '咨询');
            })
            ->first();

        if ($consultationParent) {
            // 恢复旧的菜单项
            DB::table('admin_menu')->insert([
                [
                    'parent_id' => $consultationParent->id,
                    'order' => 3,
                    'title' => 'Assigned',
                    'icon' => '',
                    'uri' => 'consultations?status=assigned',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'parent_id' => $consultationParent->id,
                    'order' => 5,
                    'title' => 'Completed',
                    'icon' => '',
                    'uri' => 'consultations?status=completed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}

