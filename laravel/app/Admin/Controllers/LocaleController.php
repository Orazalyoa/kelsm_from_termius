<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends AdminController
{
    /**
     * 切换语言
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        
        // 支持的语言列表
        $supportedLocales = ['ru', 'kk'];
        
        if (in_array($locale, $supportedLocales)) {
            // 保存到 session
            Session::put('locale', $locale);
            
            // 立即设置应用语言
            app()->setLocale($locale);
            
            // 保存到 cookie (持久化30天)
            Cookie::queue('locale', $locale, 43200); // 30 days
        }
        
        // 返回上一页并附带cookie
        return redirect()->back();
    }
}




