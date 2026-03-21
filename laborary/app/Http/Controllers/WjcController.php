<?php

namespace app\Http\Controllers;

use App\Models\LabUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\SendActivationCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


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
        //参数验证
        $validator = Validator::make($request->all(),[
            'account' => 'required|string',
        ]);
        
        if($validator->fails()){
            return response()->json([
                'code' => 400,
                'msg' => '参数错误：'.$validator->errors()->first(),
                'data' => null
            ]);
        }

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

        //频率限制
        if($user->activation_expire && $user->activation_expire->isAfter(now()->subMinute(1))){
            return response()->json([
                'code' => 429,
                'msg' => '请勿频繁获取激活码，1分钟后重试',
                'data' => null
            ]);
        }
        
        $activationCode = mt_rand(100000, 999999);
        $expireTime = now()->addMinutes(30);

        //保存激活码到数据库
        $user->activation_code = $activationCode;
        $user->activation_expire = $expireTime;
        $user->save();

        //发送激活码邮件
        try{
            Mail::to($user->email)->send(new SendActivationCodeMail($user->username, $activationCode));
            Log::info('激活码邮件发送成功',['account' => $account,'email' => $user->email]);
        }catch (\Exception $e){
            Log::error('激活码邮件发送失败',['account' => $account,'email' => $user->email,'error' => $e->getMessage()]);
            return response()->json([
                'code' => 500,
                'msg' => '激活码邮件发送失败',
                'data' => null
            ]);
        }
        
        return response()->json([
            'code' => 200,
            'msg' => '激活码发送成功,30分钟内有效',
            'data' => [
                'email' => $user->email,
                'activation_code' => env('APP_ENV') === 'dev' ? $activationCode : null
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

    //验证激活码并激活账号
    public function verifyActivationCode(\Illuminate\Http\Request $request): JsonResponse
    {
        //参数验证
        $validator = Validator::make($request->all(),[
            'account' => 'required|string',
            'activation_code' => 'required|string|size:6'
        ]);

        if($validator->fails()){
            return response()->json([
                'code' => 400,
                'msg' => '参数错误：'.$validator->errors()->first(),
                'data' => null
            ]);
        }

        $account = $request->input('account');
        $code = $request->input('activation_code');
        $user = LabUser::where('account',$account)->first();

        if(!$user){
            return response()->json([
                'code' => 404,
                'msg' => '账号不存在',
                'data' => null
            ]);
        }

        if($user->is_active){
            return response()->json([
                'code' => 403,
                'msg' => '账号已激活，无需重复操作',
                'data' => null
            ]);
        }

        //调用模型的方法验证激活码
        if(!$user->isActivationCodeValid($code)){
            return response()->json([
                'code'=> 400,
                'msg' =>'激活码无效',
                'data' => null
            ]);
        }

        //标记账号激活
        $user->markAsActivated();
        Log::info('账号激活成功',['account' => $account]);
        return response()->json([
            'code' => 200,
            'msg' => '账号激活成功',
            'data' => null
        ]);
    }
}
