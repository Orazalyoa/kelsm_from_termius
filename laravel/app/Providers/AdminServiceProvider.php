<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dcat\Admin\Admin;
use Illuminate\Support\Facades\Session;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Dcat Admin 会自动从数据库加载菜单
        // 如果需要自定义菜单，可以在这里配置
        
        // 添加语言切换下拉菜单到导航栏
        Admin::navbar(function ($navbar) {
            $navbar->right(new \App\Admin\Extensions\Nav\LanguageSwitcher());
        });
        
        // 添加自定义CSS优化表格布局
        Admin::style(<<<CSS
/* 优化咨询列表表格布局 */
.grid-table table {
    table-layout: fixed;
    width: 100%;
}

.grid-table td, .grid-table th {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle !important;
    padding: 8px 10px !important;
}

/* 操作列的按钮间距 */
.grid-table .grid__actions {
    min-width: 120px;
}

.grid-table .grid__actions a,
.grid-table .grid__actions .btn {
    margin-right: 5px;
}

/* Label标签的间距优化 */
.grid-table .label {
    display: inline-block;
    white-space: nowrap;
}

/* 下拉菜单操作按钮优化 */
.grid-dropdown-actions .dropdown-menu {
    min-width: 150px;
}

.grid-dropdown-actions .dropdown-menu a {
    padding: 5px 15px;
}

/* 响应式表格水平滚动 */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* 小屏幕优化 */
@media (max-width: 1200px) {
    .grid-table td, .grid-table th {
        font-size: 12px;
        padding: 6px 8px !important;
    }
}
CSS
        );
    }
}
