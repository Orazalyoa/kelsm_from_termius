<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Dcat\Admin\Models\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    /**
     * Operator (Dcat Admin 用户) 登录获取 JWT
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 查找管理员用户
        $admin = Administrator::where('username', $request->username)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], 401);
        }

        // 生成 JWT Token with custom claims
        $customClaims = [
            'sub' => 'operator_' . $admin->id, // 使用特殊的 subject 格式
            'is_operator' => true,
            'operator_id' => $admin->id,
            'operator_name' => $admin->name,
            'username' => $admin->username,
            'iat' => time(),
            'exp' => time() + (config('jwt.ttl', 480) * 60), // 默认 8 小时
        ];

        // 手动创建 JWT token
        $token = JWTAuth::getJWTProvider()->encode($customClaims);

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // 转换为秒
            'operator' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'username' => $admin->username,
            ]
        ]);
    }

    /**
     * 获取当前 Operator 信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            // 从 JWT 中获取 payload
            $payload = JWTAuth::parseToken()->getPayload();
            
            // 检查是否是 operator
            if (!$payload->get('is_operator')) {
                return response()->json([
                    'error' => 'Not an operator'
                ], 403);
            }

            $operatorId = $payload->get('operator_id');
            $admin = Administrator::find($operatorId);

            if (!$admin) {
                return response()->json([
                    'error' => 'Operator not found'
                ], 404);
            }

            return response()->json([
                'operator' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'username' => $admin->username,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }
    }

    /**
     * Operator 登出
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not logout'
            ], 500);
        }
    }

    /**
     * 刷新 Token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not refresh token'
            ], 500);
        }
    }
}

