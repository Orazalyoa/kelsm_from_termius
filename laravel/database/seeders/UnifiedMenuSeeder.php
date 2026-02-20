<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Menu;

class UnifiedMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 清空所有业务菜单（保留 id=1 的Dashboard和id=2的System）
        Menu::whereNotIn('id', [1, 2])->delete();

        $order = 1;

        // ============================================
        // 1. 控制台 / Dashboard
        // ============================================
        Menu::updateOrCreate(
            ['id' => 1],
            [
                'parent_id' => 0,
                'order' => $order++,
                'title' => 'Панель управления',
                'icon' => 'feather icon-bar-chart-2',
                'uri' => '/',
            ]
        );

        // ============================================
        // 2. 用户管理 / User Management
        // ============================================
        $userManagement = Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Пользователи',
            'icon' => 'feather icon-users',
            'uri' => '',
        ]);

        Menu::create([
            'parent_id' => $userManagement->id,
            'order' => $order++,
            'title' => 'Пользователи приложения',
            'icon' => '',
            'uri' => 'app-users',
        ]);

        Menu::create([
            'parent_id' => $userManagement->id,
            'order' => $order++,
            'title' => 'Администраторы',
            'icon' => '',
            'uri' => 'auth/users',
        ]);

        Menu::create([
            'parent_id' => $userManagement->id,
            'order' => $order++,
            'title' => 'Роли',
            'icon' => '',
            'uri' => 'auth/roles',
        ]);

        Menu::create([
            'parent_id' => $userManagement->id,
            'order' => $order++,
            'title' => 'Права',
            'icon' => '',
            'uri' => 'auth/permissions',
        ]);

        // ============================================
        // 3. 组织管理 / Organizations
        // ============================================
        Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Организации',
            'icon' => 'feather icon-briefcase',
            'uri' => 'organizations',
        ]);

        // ============================================
        // 4. 职业管理 / Professions
        // ============================================
        Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Профессии',
            'icon' => 'feather icon-award',
            'uri' => 'professions',
        ]);

        // ============================================
        // 5. 邀请码管理 / Invite Codes
        // ============================================
        Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Пригл. коды',
            'icon' => 'feather icon-gift',
            'uri' => 'invite-codes',
        ]);

        // ============================================
        // 6. 公告管理 / Announcements (NEW)
        // ============================================
        Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Объявления',
            'icon' => 'feather icon-bell',
            'uri' => 'announcements',
        ]);

        // ============================================
        // 7. 咨询管理 / Consultation Management
        // ============================================
        $consultationManagement = Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Консультации',
            'icon' => 'feather icon-file-text',
            'uri' => '',
        ]);

        Menu::create([
            'parent_id' => $consultationManagement->id,
            'order' => $order++,
            'title' => 'Все консультации',
            'icon' => '',
            'uri' => 'consultations',
        ]);

        Menu::create([
            'parent_id' => $consultationManagement->id,
            'order' => $order++,
            'title' => 'Ожидают',
            'icon' => '',
            'uri' => 'consultations?status=pending',
        ]);

        Menu::create([
            'parent_id' => $consultationManagement->id,
            'order' => $order++,
            'title' => 'В работе',
            'icon' => '',
            'uri' => 'consultations?status=in_progress',
        ]);

        Menu::create([
            'parent_id' => $consultationManagement->id,
            'order' => $order++,
            'title' => 'Архивированы',
            'icon' => '',
            'uri' => 'consultations?status=archived',
        ]);

        // ============================================
        // 8. 聊天管理 / Chat Management
        // ============================================
        $chatManagement = Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Чаты',
            'icon' => 'feather icon-message-square',
            'uri' => '',
        ]);

        Menu::create([
            'parent_id' => $chatManagement->id,
            'order' => $order++,
            'title' => 'Список чатов',
            'icon' => '',
            'uri' => 'chats',
        ]);

        Menu::create([
            'parent_id' => $chatManagement->id,
            'order' => $order++,
            'title' => 'Сообщения',
            'icon' => '',
            'uri' => 'messages',
        ]);

        Menu::create([
            'parent_id' => $chatManagement->id,
            'order' => $order++,
            'title' => 'Участники',
            'icon' => '',
            'uri' => 'chat-participants',
        ]);

        Menu::create([
            'parent_id' => $chatManagement->id,
            'order' => $order++,
            'title' => 'Файлы',
            'icon' => '',
            'uri' => 'chat-files',
        ]);

        // ============================================
        // 9. 系统设置 / System Settings
        // ============================================
        $systemSettings = Menu::create([
            'parent_id' => 0,
            'order' => $order++,
            'title' => 'Настройки',
            'icon' => 'feather icon-settings',
            'uri' => '',
        ]);

        Menu::create([
            'parent_id' => $systemSettings->id,
            'order' => $order++,
            'title' => 'Меню',
            'icon' => '',
            'uri' => 'auth/menu',
        ]);

        Menu::create([
            'parent_id' => $systemSettings->id,
            'order' => $order++,
            'title' => 'Журнал операций',
            'icon' => '',
            'uri' => 'auth/logs',
        ]);

        $this->command->info('✅ Unified menu structure created successfully!');
        $this->command->info('📋 Menu includes:');
        $this->command->info('   - Dashboard');
        $this->command->info('   - User Management (App Users, Admins, Roles, Permissions)');
        $this->command->info('   - Organizations');
        $this->command->info('   - Professions');
        $this->command->info('   - Invite Codes');
        $this->command->info('   - Announcements (NEW)');
        $this->command->info('   - Consultation Management (All, Pending, Assigned, In Progress, Completed)');
        $this->command->info('   - Chat Management (Chats, Messages, Participants, Files)');
        $this->command->info('   - System Settings (Menu, Operation Log)');
        $this->command->info('');
        $this->command->info('🌐 All menu items support multi-language (EN, ZH_CN, RU, KK)');
    }
}

