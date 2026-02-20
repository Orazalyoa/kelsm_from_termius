<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // utf8mb4 下 191 字符 ~ 764 bytes，索引安全
        Schema::defaultStringLength(191);
    }
}
