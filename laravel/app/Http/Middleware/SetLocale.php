<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 支持的语言列表
        $supportedLocales = ['en', 'ru', 'kk', 'zh_CN'];
        
        // 语言代码映射 (支持前端的非标准代码)
        $localeMap = [
            'zh-CN' => 'zh_CN',    // 简体中文
            'zh-Hans' => 'zh_CN',  // 简体中文
            'zh' => 'zh_CN',       // 默认中文为简体
        ];
        
        // 优先级: Query Parameter > Cookie > Session > HTTP Header > Default
        $locale = $request->get('locale')
               ?? $request->get('lang')
               ?? $request->cookie('locale')
               ?? Session::get('locale')
               ?? $request->header('Accept-Language')
               ?? config('app.locale', 'ru');
        
        // 应用语言代码映射
        $locale = $localeMap[$locale] ?? $locale;
        
        // 验证并设置语言
        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
            // 保存到 session
            Session::put('locale', $locale);
            // 保存到 cookie (持久化30天)
            Cookie::queue('locale', $locale, 43200); // 30 days
        } else {
            // 使用默认语言
            $locale = config('app.locale', 'ru');
            App::setLocale($locale);
        }
        
        return $next($request);
    }
}




