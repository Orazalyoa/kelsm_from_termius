<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Menu;

class ProjectMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 获取当前最大 order
        $maxOrder = Menu::max('order') ?? 10;

        // 1. 用户管理
        $userManagement = Menu::firstOrCreate(
            ['title' => '用户管理', 'parent_id' => 0],
            [
                'icon' => 'feather icon-users',
                'uri' => '',
                'order' => ++$maxOrder,
            ]
        );

        Menu::firstOrCreate(
            ['title' => 'App 用户', 'uri' => 'app-users'],
            [
                'parent_id' => $userManagement->id,
                'icon' => '',
                'order' => ++$maxOrder,
            ]
        );

        // 2. 组织管理
        Menu::firstOrCreate(
            ['title' => '组织管理', 'uri' => 'organizations'],
            [
                'parent_id' => 0,
                'icon' => 'feather icon-briefcase',
                'order' => ++$maxOrder,
            ]
        );

        // 3. 职业管理
        Menu::firstOrCreate(
            ['title' => '职业管理', 'uri' => 'professions'],
            [
                'parent_id' => 0,
                'icon' => 'feather icon-award',
                'order' => ++$maxOrder,
            ]
        );

        // 4. 邀请码管理
        Menu::firstOrCreate(
            ['title' => '邀请码管理', 'uri' => 'invite-codes'],
            [
                'parent_id' => 0,
                'icon' => 'feather icon-gift',
                'order' => ++$maxOrder,
            ]
        );

        // 5. 聊天管理
        $chatManagement = Menu::firstOrCreate(
            ['title' => '聊天管理', 'parent_id' => 0],
            [
                'icon' => 'feather icon-message-square',
                'uri' => '',
                'order' => ++$maxOrder,
            ]
        );

        Menu::firstOrCreate(
            ['title' => '聊天列表', 'uri' => 'chats'],
            [
                'parent_id' => $chatManagement->id,
                'icon' => '',
                'order' => ++$maxOrder,
            ]
        );

        Menu::firstOrCreate(
            ['title' => '消息管理', 'uri' => 'messages'],
            [
                'parent_id' => $chatManagement->id,
                'icon' => '',
                'order' => ++$maxOrder,
            ]
        );

        Menu::firstOrCreate(
            ['title' => '聊天参与者', 'uri' => 'chat-participants'],
            [
                'parent_id' => $chatManagement->id,
                'icon' => '',
                'order' => ++$maxOrder,
            ]
        );

        Menu::firstOrCreate(
            ['title' => '聊天文件', 'uri' => 'chat-files'],
            [
                'parent_id' => $chatManagement->id,
                'icon' => '',
                'order' => ++$maxOrder,
            ]
        );

        // 6. 咨询管理
        Menu::firstOrCreate(
            ['title' => '咨询管理', 'uri' => 'consultations'],
            [
                'parent_id' => 0,
                'icon' => 'feather icon-file-text',
                'order' => ++$maxOrder,
            ]
        );

        $this->command->info('Project menus created successfully!');
        $this->command->info('You can now see these menus in the admin panel.');
    }
}

