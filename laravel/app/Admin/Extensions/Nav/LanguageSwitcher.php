<?php

namespace App\Admin\Extensions\Nav;

use Dcat\Admin\Admin;
use Illuminate\Contracts\Support\Renderable;

class LanguageSwitcher implements Renderable
{
    /**
     * @return string
     */
    public function render()
    {
        $locale = app()->getLocale();
        
        $languages = [
            'en' => ['name' => 'English', 'flag' => '🇬🇧'],
            'ru' => ['name' => 'Русский', 'flag' => '🇷🇺'],
            'kk' => ['name' => 'Қазақша', 'flag' => '🇰🇿'],
            'zh_CN' => ['name' => '简体中文', 'flag' => '🇨🇳'],
        ];
        
        $currentLanguage = $languages[$locale] ?? $languages['ru'];
        
        $items = '';
        foreach ($languages as $code => $lang) {
            $active = $code === $locale ? 'active' : '';
            $items .= <<<HTML
                <a class="dropdown-item {$active}" href="#" onclick="switchLanguage('{$code}'); return false;">
                    <span style="font-size: 16px; margin-right: 8px;">{$lang['flag']}</span>
                    {$lang['name']}
                </a>
HTML;
        }
        
        $script = <<<JS
        <script>
        function switchLanguage(locale) {
            // 发送POST请求切换语言
            fetch('/admin/locale/switch?locale=' + locale, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin'
            }).then(function() {
                // 刷新页面
                window.location.reload();
            });
        }
        </script>
JS;
        
        return <<<HTML
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                <span style="font-size: 18px; margin-right: 5px;">{$currentLanguage['flag']}</span>
                <span>{$currentLanguage['name']}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                {$items}
            </div>
        </li>
        {$script}
HTML;
    }
}


