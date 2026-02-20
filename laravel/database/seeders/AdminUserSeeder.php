<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Models\Role;
use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Menu;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建管理员角色
        $role = Role::firstOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
            ]
        );

        // 创建所有权限
        $allPermissions = Permission::all();
        if ($allPermissions->isNotEmpty()) {
            $role->permissions()->sync($allPermissions->pluck('id'));
        }

        // 创建管理员用户
        $admin = Administrator::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => bcrypt('admin'),
                'name' => 'Administrator',
            ]
        );

        // 分配角色
        $admin->roles()->sync([$role->id]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Username: admin');
        $this->command->info('Password: admin');
        $this->command->warn('Please change the default password after first login!');
    }
}

