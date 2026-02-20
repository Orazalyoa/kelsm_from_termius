<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ValidateInviteRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\InviteCode;
use App\Services\AuthService;
use App\Services\InviteCodeService;
use App\Services\OrganizationService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request)
    {
        // Validate user_type
        if ($request->user_type === User::TYPE_LAWYER) {
            return response()->json(['error' => __('api.auth.lawyers_admin_only')], 403);
        }
        
        // Validate invite code if provided
        $invite = null;
        $actualUserType = $request->user_type; // 实际注册的用户类型，可能被邀请码类型覆盖
        
        if ($request->has('invite_code') && !empty($request->invite_code)) {
            $invite = InviteCodeService::validate($request->invite_code);
            if (!$invite) {
                return response()->json([
                    'error' => __('api.auth.invalid_invite_code'),
                    'message' => '邀请码无效、已过期或使用次数已达上限'
                ], 422);
            }
            
            // 如果注册类型是 expert，但邀请码是 company_admin，则使用邀请码的类型
            if ($request->user_type === User::TYPE_EXPERT && $invite->user_type === InviteCode::USER_TYPE_COMPANY_ADMIN) {
                $actualUserType = User::TYPE_COMPANY_ADMIN;
            } elseif ($request->user_type === User::TYPE_COMPANY_ADMIN && $invite->user_type === InviteCode::USER_TYPE_EXPERT) {
                // 如果注册类型是 company_admin，但邀请码是 expert，则使用邀请码的类型
                $actualUserType = User::TYPE_EXPERT;
            } elseif ($invite->user_type !== $request->user_type) {
                // 其他情况下的类型不匹配，返回错误
                $inviteTypeName = $invite->user_type === InviteCode::USER_TYPE_EXPERT ? '专家' : '公司管理员';
                $requestTypeName = $request->user_type === User::TYPE_EXPERT ? '专家' : '公司管理员';
                return response()->json([
                    'error' => __('api.auth.invalid_invite_code'),
                    'message' => "邀请码类型不匹配。邀请码类型为：{$inviteTypeName}，注册类型为：{$requestTypeName}"
                ], 422);
            }
        } elseif ($request->user_type === User::TYPE_EXPERT) {
            // Experts must provide invite code
            return response()->json(['error' => __('request_validation.register.invite_code_required_if')], 422);
        }
        
        try {
            DB::beginTransaction();
            
            // Prepare user data
            $userData = $request->validated();
            
            // 使用实际用户类型（可能被邀请码类型覆盖）
            $userData['user_type'] = $actualUserType;
            
            // If user has invite code, copy permissions from invite code
            // Note: company_admin will have full permissions regardless, but we still copy for consistency
            if ($invite) {
                $userData['permissions'] = $invite->permissions;
            }
            
            // Create user
            $user = AuthService::register($userData);
            
            // If user has invite code, join organization
            if ($invite) {
                // Determine role based on actual user_type
                $role = ($actualUserType === User::TYPE_COMPANY_ADMIN) ? 'admin' : 'member';
                OrganizationService::addMember($invite->organization_id, $user->id, $role, $invite->created_by);
                InviteCodeService::markUsed($invite->id, $user->id);
            }
            
            DB::commit();
            
            // Generate JWT token
            $token = auth('api')->login($user);
            
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => new UserResource($user)
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => __('api.auth.registration_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        $loginField = $this->getLoginField($request->identifier);
        $credentials = [
            $loginField => $request->identifier,
            'password' => $request->password
        ];
        
        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => __('api.auth.invalid_credentials')], 401);
        }
        
        $user = auth('api')->user();
        User::where('id', $user->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => __('api.auth.logged_out')]);
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        return response()->json([
            'access_token' => JWTAuth::refresh(),
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        $user = auth('api')->user();
        $user = $user->load(['professions', 'organizations']);
        return response()->json([
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Validate invite code
     */
    public function validateInvite(ValidateInviteRequest $request)
    {
        $invite = InviteCodeService::validate($request->invite_code);
        
        if (!$invite) {
            return response()->json(['error' => __('api.auth.invalid_expired_invite')], 422);
        }
        
        return response()->json([
            'valid' => true,
            'user_type' => $invite->user_type, // 返回邀请码对应的用户类型
            'organization' => [
                'id' => $invite->organization->id,
                'name' => $invite->organization->name,
                'company_id' => $invite->organization->company_id
            ]
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = auth('api')->user();
        
        if (!AuthService::changePassword($user, $request->current_password, $request->password)) {
            return response()->json(['error' => __('api.auth.current_password_incorrect')], 422);
        }
        
        return response()->json([
            'message' => __('api.auth.password_changed')
        ]);
    }

    /**
     * Send email OTP
     */
    public function sendEmailOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'purpose' => 'string|in:registration,reset_password,verification'
        ]);

        try {
            $result = OtpService::sendEmailOTP(
                $request->email,
                $request->purpose ?? 'verification'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send OTP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send phone OTP
     */
    public function sendPhoneOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'purpose' => 'string|in:registration,reset_password,verification'
        ]);

        try {
            $result = OtpService::sendPhoneOTP(
                $request->phone,
                $request->purpose ?? 'verification'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => __('api.auth.otp_sent_failed') . ': ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify email OTP
     */
    public function verifyEmailOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'purpose' => 'string|in:registration,reset_password,verification'
        ]);

        $isValid = OtpService::verifyEmailOTP(
            $request->email,
            $request->code,
            $request->purpose ?? 'verification'
        );

        if (!$isValid) {
            return response()->json(['error' => __('api.auth.otp_invalid_expired')], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => __('api.auth.otp_verified')
        ]);
    }

    /**
     * Verify phone OTP
     */
    public function verifyPhoneOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string',
            'purpose' => 'string|in:registration,reset_password,verification'
        ]);

        $isValid = OtpService::verifyPhoneOTP(
            $request->phone,
            $request->code,
            $request->purpose ?? 'verification'
        );

        if (!$isValid) {
            return response()->json(['error' => __('api.auth.otp_invalid_expired')], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => __('api.auth.otp_verified')
        ]);
    }

    /**
     * Reset password using OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify OTP
        $isValid = OtpService::verifyEmailOTP(
            $request->email,
            $request->code,
            'reset_password'
        );

        if (!$isValid) {
            return response()->json(['error' => __('api.auth.otp_invalid_expired')], 422);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => __('api.auth.user_not_found')], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => __('api.auth.password_reset')
        ]);
    }

    /**
     * Determine login field based on identifier
     * Improved logic: prioritize email validation, then check phone format
     */
    private function getLoginField($identifier)
    {
        // First check if it's a valid email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        
        // Remove spaces and check if it's a phone number format
        $cleaned = preg_replace('/\s+/', '', $identifier);
        // Phone should start with + and have 10-15 digits, or just have 10-15 digits
        if (preg_match('/^\+?[1-9]\d{9,14}$/', $cleaned)) {
            return 'phone';
        }
        
        // Default to email for backward compatibility
        return 'email';
    }
}
