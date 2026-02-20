<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProfessionController;
use App\Http\Controllers\Api\InviteCodeController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\ConsultationAIController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Admin\AdminAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Operator (Admin) Authentication routes
Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::post('refresh', [AdminAuthController::class, 'refresh']);
        Route::get('me', [AdminAuthController::class, 'me']);
    });
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('validate-invite', [AuthController::class, 'validateInvite']);
    
    // OTP routes
    Route::post('send-email-otp', [AuthController::class, 'sendEmailOTP']);
    Route::post('send-phone-otp', [AuthController::class, 'sendPhoneOTP']);
    Route::post('verify-email-otp', [AuthController::class, 'verifyEmailOTP']);
    Route::post('verify-phone-otp', [AuthController::class, 'verifyPhoneOTP']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Public routes
Route::get('professions', [ProfessionController::class, 'index']);

// Availability check routes (public for registration)
Route::get('check-email', [UserController::class, 'checkEmail']);
Route::get('check-phone', [UserController::class, 'checkPhone']);
Route::get('check-username', [UserController::class, 'checkUsername']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Announcements
    Route::prefix('announcements')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index']);
        Route::get('{id}', [AnnouncementController::class, 'show']);
    });
    
    // Password change
    Route::post('change-password', [AuthController::class, 'changePassword']);
    
    // User profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserController::class, 'show']);
        Route::put('/', [UserController::class, 'update']);
        Route::post('avatar', [UserController::class, 'uploadAvatar']);
        Route::get('organizations', [UserController::class, 'organizations']);
        Route::get('stats', [UserController::class, 'stats']);
        Route::get('notification-settings', [UserController::class, 'getNotificationSettings']);
        Route::put('notification-settings', [UserController::class, 'updateNotificationSettings']);
        // Route::get('privacy-settings', [UserController::class, 'getPrivacySettings']);
        // Route::put('privacy-settings', [UserController::class, 'updatePrivacySettings']);
        Route::post('delete-account', [UserController::class, 'deleteAccount']);
        Route::get('activity-logs', [UserController::class, 'activityLogs']);
    });
    
    // Organization routes - accessible to organization members
    Route::prefix('organizations')->group(function () {
        // All authenticated users can access their organizations
        Route::get('/', [OrganizationController::class, 'index']);
        Route::get('{id}', [OrganizationController::class, 'show']);
        Route::get('{id}/stats', [OrganizationController::class, 'stats']);
        Route::get('{id}/members', [OrganizationController::class, 'members']);
        
        // Only company_admin can create organizations
        Route::middleware('user.type:company_admin')->group(function () {
            Route::post('/', [OrganizationController::class, 'store']);
        });
        
        // Organization management - requires canManage permission (checked in controller)
        Route::put('{id}', [OrganizationController::class, 'update']);
        Route::delete('{id}', [OrganizationController::class, 'destroy']);
        Route::post('{id}/members', [OrganizationController::class, 'addMember']);
        Route::put('{id}/members/{userId}', [OrganizationController::class, 'updateMember']);
        Route::delete('{id}/members/{userId}', [OrganizationController::class, 'removeMember']);
    });
    
    // Invite code management (Company Admin only)
    Route::middleware('user.type:company_admin')->prefix('invite-codes')->group(function () {
        Route::post('/', [InviteCodeController::class, 'store']);
        Route::post('batch', [InviteCodeController::class, 'batchCreate']);
        Route::get('/', [InviteCodeController::class, 'index']);
        Route::get('stats', [InviteCodeController::class, 'stats']);
        Route::get('export', [InviteCodeController::class, 'export']);
        Route::post('check', [InviteCodeController::class, 'check']);
        Route::get('{id}', [InviteCodeController::class, 'show']);
        Route::get('{id}/uses', [InviteCodeController::class, 'uses']);
        Route::delete('{id}', [InviteCodeController::class, 'destroy']);
    });
    
    // Consultation management
    Route::prefix('consultations')->group(function () {
        Route::get('/', [ConsultationController::class, 'index']);
        Route::post('/', [ConsultationController::class, 'store']);
        Route::get('statistics', [ConsultationController::class, 'statistics']);
        Route::get('{id}', [ConsultationController::class, 'show']);
        Route::put('{id}', [ConsultationController::class, 'update']);
        Route::put('{id}/status', [ConsultationController::class, 'updateStatus']);
        
        // Client actions
        Route::post('{id}/withdraw', [ConsultationController::class, 'withdraw']);
        Route::post('{id}/escalate-priority', [ConsultationController::class, 'escalatePriority']);
        Route::post('{id}/archive', [ConsultationController::class, 'archive']);
        Route::post('{id}/unarchive', [ConsultationController::class, 'unarchive']);
        
        // File management
        Route::post('{id}/files', [ConsultationController::class, 'uploadFile']);
        Route::get('{id}/files/{fileId}/versions', [ConsultationController::class, 'getFileVersions']);
        Route::delete('{id}/files/{fileId}', [ConsultationController::class, 'deleteFile']);
        
        // AI-powered features (with rate limiting)
        Route::middleware('ai.ratelimit:20,1')->group(function () {
            Route::post('{id}/ai/analyze', [ConsultationAIController::class, 'analyze']);
            Route::post('{id}/ai/summarize', [ConsultationAIController::class, 'summarize']);
            Route::post('{id}/ai/suggest-priority', [ConsultationAIController::class, 'suggestPriority']);
        });
    });
    
    // Notification management
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('clear-read', [NotificationController::class, 'clearRead']);
        Route::post('batch-delete', [NotificationController::class, 'batchDestroy']);
        Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);
    });
});

// Public file download route (supports both JWT auth and signed URL)
Route::get('consultations/{id}/files/{fileId}', [ConsultationController::class, 'downloadFile'])
    ->name('consultation.file.download');
