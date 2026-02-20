<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Role;

class DcatAdminInitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建权限
        $this->createPermissions();
        
        // 创建角色
        $this->createRoles();
        
        // 创建菜单
        $this->createMenus();

        $this->command->info('Dcat Admin initialized successfully!');
    }

    protected function createPermissions()
    {
        $permissions = [
            [
                'name' => 'All permission',
                'slug' => '*',
                'http_method' => '',
                'http_path' => '*',
                'order' => 1,
            ],
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'http_method' => 'GET',
                'http_path' => '/',
                'order' => 2,
            ],
            [
                'name' => 'Login',
                'slug' => 'auth.login',
                'http_method' => '',
                'http_path' => '/auth/login\r\n/auth/logout',
                'order' => 3,
            ],
            [
                'name' => 'User setting',
                'slug' => 'auth.setting',
                'http_method' => 'GET,PUT',
                'http_path' => '/auth/setting',
                'order' => 4,
            ],
            [
                'name' => 'Auth management',
                'slug' => 'auth.management',
                'http_method' => '',
                'http_path' => '/auth/roles\r\n/auth/permissions\r\n/auth/menu\r\n/auth/logs',
                'order' => 5,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $this->command->info('Permissions created.');
    }

    protected function createRoles()
    {
        // 管理员角色
        $adminRole = Role::firstOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
            ]
        );

        // 给管理员角色分配所有权限
        $allPermission = Permission::where('slug', '*')->first();
        if ($allPermission) {
            $adminRole->permissions()->sync([$allPermission->id]);
        }

        $this->command->info('Roles created.');
    }

    protected function createMenus()
    {
        $menus = [
            [
                'parent_id' => 0,
                'order' => 1,
                'title' => 'Dashboard',
                'icon' => 'feather icon-bar-chart-2',
                'uri' => '/',
            ],
            [
                'parent_id' => 0,
                'order' => 2,
                'title' => 'Admin',
                'icon' => 'feather icon-settings',
                'uri' => '',
            ],
            [
                'parent_id' => 0, // 将在创建后更新
                'order' => 3,
                'title' => 'Users',
                'icon' => '',
                'uri' => 'auth/users',
            ],
            [
                'parent_id' => 0, // 将在创建后更新
                'order' => 4,
                'title' => 'Roles',
                'icon' => '',
                'uri' => 'auth/roles',
            ],
            [
                'parent_id' => 0, // 将在创建后更新
                'order' => 5,
                'title' => 'Permission',
                'icon' => '',
                'uri' => 'auth/permissions',
            ],
            [
                'parent_id' => 0, // 将在创建后更新
                'order' => 6,
                'title' => 'Menu',
                'icon' => '',
                'uri' => 'auth/menu',
            ],
            [
                'parent_id' => 0, // 将在创建后更新
                'order' => 7,
                'title' => 'Extensions',
                'icon' => '',
                'uri' => 'auth/extensions',
            ],
        ];

        // 创建 Dashboard
        $dashboard = Menu::firstOrCreate(
            ['title' => 'Dashboard', 'uri' => '/'],
            $menus[0]
        );

        // 创建 Admin 父菜单
        $admin = Menu::firstOrCreate(
            ['title' => 'Admin', 'parent_id' => 0],
            $menus[1]
        );

        // 创建子菜单
        $subMenus = [
            ['title' => 'Users', 'uri' => 'auth/users', 'icon' => '', 'order' => 3],
            ['title' => 'Roles', 'uri' => 'auth/roles', 'icon' => '', 'order' => 4],
            ['title' => 'Permission', 'uri' => 'auth/permissions', 'icon' => '', 'order' => 5],
            ['title' => 'Menu', 'uri' => 'auth/menu', 'icon' => '', 'order' => 6],
            ['title' => 'Extensions', 'uri' => 'auth/extensions', 'icon' => '', 'order' => 7],
        ];

        foreach ($subMenus as $subMenu) {
            Menu::firstOrCreate(
                ['title' => $subMenu['title'], 'uri' => $subMenu['uri']],
                array_merge($subMenu, ['parent_id' => $admin->id])
            );
        }

        $this->command->info('Menus created.');
    }
}

