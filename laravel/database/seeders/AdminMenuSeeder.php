<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Role;

class AdminMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建权限
        $permissions = [
            'users' => 'User Management',
            'organizations' => 'Organization Management',
            'professions' => 'Profession Management',
            'invite-codes' => 'Invite Code Management',
        ];

        foreach ($permissions as $slug => $name) {
            Permission::firstOrCreate([
                'slug' => $slug,
            ], [
                'name' => $name,
                'http_method' => '',
                'http_path' => "/{$slug}*",
            ]);
        }

        // 创建角色
        $adminRole = Role::firstOrCreate([
            'slug' => 'administrator',
        ], [
            'name' => 'Administrator',
        ]);

        // 分配权限给管理员角色
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // 创建菜单
        $this->createMenus();
    }

    private function createMenus()
    {
        // 仪表板
        $dashboard = Menu::firstOrCreate([
            'title' => 'Dashboard',
            'uri' => '/',
        ], [
            'icon' => 'fa-dashboard',
            'order' => 1,
        ]);

        // 用户管理
        $users = Menu::firstOrCreate([
            'title' => 'User Management',
            'uri' => '/users',
        ], [
            'icon' => 'fa-users',
            'order' => 2,
        ]);

        // 组织管理
        $organizations = Menu::firstOrCreate([
            'title' => 'Organization Management',
            'uri' => '/organizations',
        ], [
            'icon' => 'fa-building',
            'order' => 3,
        ]);

        // 职业管理
        $professions = Menu::firstOrCreate([
            'title' => 'Profession Management',
            'uri' => '/professions',
        ], [
            'icon' => 'fa-briefcase',
            'order' => 4,
        ]);

        // 邀请码管理
        $inviteCodes = Menu::firstOrCreate([
            'title' => 'Invite Code Management',
            'uri' => '/invite-codes',
        ], [
            'icon' => 'fa-ticket-alt',
            'order' => 5,
        ]);
    }
}
