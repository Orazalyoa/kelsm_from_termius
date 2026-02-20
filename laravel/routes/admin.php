<?php

use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

// Dcat Admin 自动注册的路由（包括登录、菜单、用户等管理页面）
Admin::routes();

// 业务模块路由
Route::group([
    'prefix' => config('admin.route.prefix'),
    'middleware' => config('admin.route.middleware'),
], function () {
    // 首页
    Route::get('/', [\App\Admin\Controllers\HomeController::class, 'index'])->name('admin.home');
    
    // 用户管理
    Route::resource('app-users', \App\Admin\Controllers\AppUserController::class);
    
    // 组织管理
    Route::resource('organizations', \App\Admin\Controllers\OrganizationController::class);
    
    // 职业管理
    Route::resource('professions', \App\Admin\Controllers\ProfessionController::class);
    
    // 邀请码管理
    Route::resource('invite-codes', \App\Admin\Controllers\InviteCodeController::class);
    
    // 公告管理
    Route::resource('announcements', \App\Admin\Controllers\AnnouncementController::class);
    
    // 聊天管理
    Route::resource('chats', \App\Admin\Controllers\ChatController::class);
    Route::resource('messages', \App\Admin\Controllers\MessageController::class);
    Route::resource('chat-participants', \App\Admin\Controllers\ChatParticipantController::class);
    Route::resource('chat-files', \App\Admin\Controllers\ChatFileController::class);
    
    // 咨询管理
    Route::resource('consultations', \App\Admin\Controllers\ConsultationController::class);
    Route::post('consultations/{id}/assign-lawyer', [\App\Admin\Controllers\ConsultationController::class, 'assignLawyer'])
        ->name('consultations.assign-lawyer');
    
    // 语言切换
    Route::get('locale/switch', [\App\Admin\Controllers\LocaleController::class, 'switch'])
        ->name('admin.locale.switch');
});
