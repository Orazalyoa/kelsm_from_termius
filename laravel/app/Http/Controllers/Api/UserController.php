<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserSettings;
use App\Models\ActivityLog;
use App\Models\Consultation;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     */
    public function show()
    {
        $user = auth('api')->user();
        $user = $user->load(['professions', 'organizations']);
        return response()->json([
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = auth('api')->user();
        
        try {
            $updatedUser = AuthService::updateProfile($user, $request->validated());
            $updatedUser->load(['professions', 'organizations']);
            
            return response()->json([
                'message' => __('api.user.profile_updated'),
                'user' => new UserResource($updatedUser)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.user.profile_update_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        
        $user = auth('api')->user();
        
        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->avatar = $path;
        $user->save();
        $user->refresh();
        
        return response()->json([
            'message' => __('api.user.avatar_uploaded'),
            'avatar' => $path,
            'avatar_url' => asset('storage/' . $path),
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Get user's organizations
     */
    public function organizations()
    {
        $user = auth('api')->user();
        $organizations = $user->organizations;
        
        return response()->json([
            'organizations' => $organizations
        ]);
    }

    /**
     * Check if email is available
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $exists = User::where('email', $request->email)->exists();

        return response()->json([
            'available' => !$exists,
            'email' => $request->email
        ]);
    }

    /**
     * Check if phone is available
     */
    public function checkPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $exists = User::where('phone', $request->phone)->exists();

        return response()->json([
            'available' => !$exists,
            'phone' => $request->phone
        ]);
    }

    /**
     * Check if username is available
     */
    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3'
        ]);

        // Currently not using username field, but preparing for future
        $exists = User::where('email', $request->username)
            ->orWhere('phone', $request->username)
            ->exists();

        return response()->json([
            'available' => !$exists,
            'username' => $request->username
        ]);
    }

    /**
     * Get user statistics
     */
    public function stats()
    {
        $user = auth('api')->user();

        $stats = [
            'consultations' => [
                'total' => Consultation::where('created_by', $user->id)->count(),
                'pending' => Consultation::where('created_by', $user->id)->where('status', 'pending')->count(),
                'in_progress' => Consultation::where('created_by', $user->id)->where('status', 'in_progress')->count(),
                'archived' => Consultation::where('created_by', $user->id)->where('status', 'archived')->count(),
            ],
            'organizations' => $user->organizations()->count(),
            'member_since' => $user->created_at->toDateString(),
            'last_login' => $user->last_login_at ? $user->last_login_at->toDateTimeString() : null,
        ];

        // Add expert-specific stats
        if ($user->user_type === User::TYPE_EXPERT) {
            $stats['professions_count'] = $user->professions()->count();
        }

        // Add company admin stats
        if ($user->user_type === User::TYPE_COMPANY_ADMIN) {
            $stats['invite_codes_created'] = $user->inviteCodes()->count();
        }

        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Get notification settings
     */
    public function getNotificationSettings()
    {
        $user = auth('api')->user();
        $settings = UserSettings::firstOrCreate(
            ['user_id' => $user->id],
            ['notification_settings' => UserSettings::defaultNotificationSettings()]
        );

        return response()->json([
            'settings' => $settings->notification_settings ?? UserSettings::defaultNotificationSettings()
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'consultation_updates' => 'boolean',
            'chat_messages' => 'boolean',
            'invite_code_used' => 'boolean',
        ]);

        $settings = UserSettings::updateOrCreate(
            ['user_id' => $user->id],
            ['notification_settings' => $request->all()]
        );

        ActivityLog::log(
            $user->id,
            'notification_settings_updated',
            'UserSettings',
            $settings->id
        );

        return response()->json([
            'message' => __('api.user.notification_settings_updated'),
            'settings' => $settings->notification_settings
        ]);
    }

    /**
     * Get privacy settings
     */
    public function getPrivacySettings()
    {
        $user = auth('api')->user();
        $settings = UserSettings::firstOrCreate(
            ['user_id' => $user->id],
            ['privacy_settings' => UserSettings::defaultPrivacySettings()]
        );

        return response()->json([
            'settings' => $settings->privacy_settings ?? UserSettings::defaultPrivacySettings()
        ]);
    }

    /**
     * Update privacy settings
     */
    public function updatePrivacySettings(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'profile_visibility' => 'string|in:public,organization,private',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'allow_messages' => 'boolean',
        ]);

        $settings = UserSettings::updateOrCreate(
            ['user_id' => $user->id],
            ['privacy_settings' => $request->all()]
        );

        ActivityLog::log(
            $user->id,
            'privacy_settings_updated',
            'UserSettings',
            $settings->id
        );

        return response()->json([
            'message' => __('api.user.privacy_settings_updated'),
            'settings' => $settings->privacy_settings
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $user = auth('api')->user();

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => __('api.user.invalid_password')], 422);
        }

        try {
            DB::beginTransaction();

            // Log the deletion
            ActivityLog::log(
                $user->id,
                'account_deleted',
                'User',
                $user->id,
                ['deleted_at' => now()]
            );

            // Soft delete or anonymize user data
            // For now, we'll use soft delete (need to add SoftDeletes trait to User model)
            $user->delete();

            DB::commit();

            // Logout user
            auth('api')->logout();

            return response()->json([
                'message' => __('api.user.account_deleted')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => __('api.user.account_deletion_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get user activity logs
     */
    public function activityLogs(Request $request)
    {
        $user = auth('api')->user();

        $perPage = $request->get('per_page', 20);
        $logs = ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($logs);
    }
}