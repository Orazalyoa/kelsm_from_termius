<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;

class ConsultationMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建咨询相关权限
        Permission::firstOrCreate([
            'slug' => 'consultations',
        ], [
            'name' => 'Consultation Management',
            'http_method' => '',
            'http_path' => '/consultations*',
        ]);

        // 查找或创建咨询管理主菜单
        $consultationMenu = Menu::firstOrCreate([
            'title' => 'Consultations',
            'uri' => '',
        ], [
            'icon' => 'fa-gavel',
            'order' => 6,
            'parent_id' => 0,
        ]);

        // 所有咨询
        Menu::firstOrCreate([
            'title' => 'All Consultations',
            'uri' => '/consultations',
        ], [
            'icon' => '',
            'order' => 1,
            'parent_id' => $consultationMenu->id,
        ]);

        // 待处理
        Menu::firstOrCreate([
            'title' => 'Pending',
            'uri' => '/consultations?status=pending',
        ], [
            'icon' => '',
            'order' => 2,
            'parent_id' => $consultationMenu->id,
        ]);

        // 进行中
        Menu::firstOrCreate([
            'title' => 'In Progress',
            'uri' => '/consultations?status=in_progress',
        ], [
            'icon' => '',
            'order' => 3,
            'parent_id' => $consultationMenu->id,
        ]);

        // 已归档
        Menu::firstOrCreate([
            'title' => 'Archived',
            'uri' => '/consultations?status=archived',
        ], [
            'icon' => '',
            'order' => 4,
            'parent_id' => $consultationMenu->id,
        ]);

        // 聊天管理主菜单（如果不存在）
        $chatMenu = Menu::firstOrCreate([
            'title' => 'Chat Management',
            'uri' => '',
        ], [
            'icon' => 'fa-comments',
            'order' => 7,
            'parent_id' => 0,
        ]);

        // 聊天列表
        Menu::firstOrCreate([
            'title' => 'Chats',
            'uri' => '/chats',
        ], [
            'icon' => '',
            'order' => 1,
            'parent_id' => $chatMenu->id,
        ]);

        // 消息
        Menu::firstOrCreate([
            'title' => 'Messages',
            'uri' => '/messages',
        ], [
            'icon' => '',
            'order' => 2,
            'parent_id' => $chatMenu->id,
        ]);
    }
}

