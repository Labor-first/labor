<?php

namespace app\Http\Controllers;

use App\Models\LabUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class WjcController extends Controller
{
    public function login(\Illuminate\Http\Request $request): JsonResponse
    {
        $credentials = $request->only('account', 'password');
        $remember = $request->input('remember_me', false);
        
        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'code' => 401,
                'msg' => '账号密码错误或账号不存在',
                'data' => null
            ]);
        }
        
        $user = auth('api')->user();
        
        $ttl = $remember ? 60 * 24 * 7 : 60 * 24;
        JWTAuth::factory()->setTTL($ttl);
        $token = JWTAuth::claims(['exp' => time() + $ttl * 60])->fromUser($user);
        
        return response()->json([
            'code' => 200,
            'msg' => '登录成功',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'nickname' => $user->username,
                'role' => $user->role,
                'account' => $user->account,
                'token' => $token
            ]
        ]);
    }

    public function sendActivationCode(\Illuminate\Http\Request $request): JsonResponse
    {
        $account = $request->input('account');
        $user = LabUser::where('account', $account)->first();
        
        if (!$user) {
            return response()->json([
                'code' => 404,
                'msg' => '账号不存在',
                'data' => null
            ]);
        }
        
        if ($user->is_active) {
            return response()->json([
                'code' => 403,
                'msg' => '账号已激活，无法重新发送',
                'data' => null
            ]);
        }
        
        $activationCode = mt_rand(100000, 999999);
        
        return response()->json([
            'code' => 200,
            'msg' => '激活码发送成功',
            'data' => [
                'email' => $user->email,
                'activation_code' => $activationCode
            ]
        ]);
    }

    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'code' => 200,
                'msg' => '登出成功',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 401,
                'msg' => 'token已过期，请重新登录',
                'data' => null
            ]);
        }
    }

    public function updateInfo(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $data = $request->only('username', 'phone', 'email');
        
        if ($request->has('new_password')) {
            if (!Hash::check($request->input('old_password'), $user->password_hash)) {
                return response()->json([
                    'code' => 403,
                    'msg' => '原密码错误',
                    'data' => null
                ]);
            }
            $data['password_hash'] = Hash::make($request->input('new_password'));
        }
        
        LabUser::where('id', $user->id)->update($data);
        
        return response()->json([
            'code' => 200,
            'msg' => '更新成功',
            'data' => null
        ]);
    }
}
