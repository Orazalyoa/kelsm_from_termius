<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupChatAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:setup-chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动配置 Dcat Admin 聊天管理菜单';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('开始配置聊天管理菜单...');

        try {
            DB::beginTransaction();

            // 创建主菜单
            $parentId = DB::table('admin_menu')->insertGetId([
                'parent_id' => 0,
                'order' => 5,
                'title' => '聊天管理',
                'icon' => 'fa-comments',
                'uri' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("✓ 创建主菜单: 聊天管理 (ID: {$parentId})");

            // 创建子菜单
            $menus = [
                [
                    'parent_id' => $parentId,
                    'order' => 1,
                    'title' => '聊天列表',
                    'icon' => 'fa-comment-dots',
                    'uri' => 'admin/chats',
                ],
                [
                    'parent_id' => $parentId,
                    'order' => 2,
                    'title' => '消息记录',
                    'icon' => 'fa-envelope',
                    'uri' => 'admin/messages',
                ],
                [
                    'parent_id' => $parentId,
                    'order' => 3,
                    'title' => '参与者管理',
                    'icon' => 'fa-users',
                    'uri' => 'admin/chat-participants',
                ],
                [
                    'parent_id' => $parentId,
                    'order' => 4,
                    'title' => '文件管理',
                    'icon' => 'fa-file-alt',
                    'uri' => 'admin/chat-files',
                ],
            ];

            foreach ($menus as $menu) {
                $menu['created_at'] = now();
                $menu['updated_at'] = now();
                $id = DB::table('admin_menu')->insertGetId($menu);
                $this->info("✓ 创建子菜单: {$menu['title']} (ID: {$id})");
            }

            DB::commit();

            $this->info('');
            $this->info('✅ 聊天管理菜单配置成功！');
            $this->info('');
            $this->info('请访问以下链接查看：');
            $this->info('  - 聊天列表: /admin/chats');
            $this->info('  - 消息记录: /admin/messages');
            $this->info('  - 参与者管理: /admin/chat-participants');
            $this->info('  - 文件管理: /admin/chat-files');
            $this->info('');
            $this->info('如需清除缓存，请运行: php artisan admin:clear-cache');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ 配置失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}




