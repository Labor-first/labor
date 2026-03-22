<?php

namespace app\Http\Controllers;

use App\Models\ApplicationForm;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class FmyController extends Controller
{
    //获取个人信息（需要登录，即需要token）
    public function me(){
        try{
            //从当前的 HTTP 请求头中提取 JWT Token
            $user=JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'message' => '用户没找到'
                ], 401);
            }
            return response()->json([
                'code'=>'200',
                'message'=>'获取成功',
                'data'=>[
                    'id'=>$user->id,
                    'name'=>$user->username,
                    'phone'=>$user->phone,
                    'email'=>$user->email,
                    'is_active'=>$user->is_active,
                    'role'=>$user->role,
                    'department_id'=>$user->department,
                    'last_login_at'=>$user->last_login_at,
                ]
            ]);
        }
        catch(\Exception $e){
            return response()->json([
                'code'=>'401',
                'message'=>'获取失败'.$e->getMessage(),
                'data'=>null
            ],401);
        }
    }


    //修改密码（需要登录,即需要token）
    public function ChangePassword()
    {
        try {
            //从当前的 HTTP 请求头中提取 JWT Token
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'message' => '用户没找到'
                ], 401);
            }
            //验证器
            //request()->all指提取当前请求中所有的输入数据
            $validator = Validator::make(request()->all(), [
                'old_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '参数验证失败',
                    'errors' => $validator->errors(),//获取错误提示
                ], 422);
            }

            //获取干净的数据 (关键步骤),$data 里绝对不会有 'role' 或其他未定义的字段
            $data = $validator->validated();

            //验证旧密码是否正确
            if (!Hash::check($data['old_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => '旧密码错误'
                ], 400);
            }

            //对新密码使用Hash::make进行加密
            $user->password = Hash::make($data['new_password']);
            $user->save();

            //成功后响应
            return response()->json([
                'success' => true,
                'message' => '密码修改成功',
                // 返回新的Token
                'token' => JWTAuth::fromUser($user),
            ], 200);
        }
        //失败后响应
        catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token 已过期，请重新登录'
            ], 401);
        }
        catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token 无效'
            ], 401);
        }
        catch (UserNotDefinedException $e) {
            return response()->json([
                'success' => false,
                'message' => '用户不存在'
            ], 404);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message'=>'修改失败，服务器内部出错'
            ], 500);
        }
    }


    //提交报名（需要登录,即需要token）
    public function RegistrationStore(){
        $user=JWTAuth::parseToken()->authenticate();
        if (!$user) {
            return response()->json([
                'message' => '用户没找到'
            ], 401);
        }
        $validator = Validator::make(request()->all(), [
            'config_id'=>'required|integer|exists:registration_config,id',
            'class'=>'required|integer',
            'academy'=>'required|string',
            'major'=>'required|string',
            'director_name'=>'required|string',
            'sign_reason'=>'required|string|min:10'
        ],[
            'config_id.required'=>'config_id不能为空',
            'class.required'=>'班级不能为空',
            'academy.required'=>'学院不能为空',
            'major.required'=>'专业不能为空',
            'director_name.required'=>'导员姓名不能为空',
            'sign_reason.required'=>'申请理由不能为空',
            'sign_reason.min'=>'申请理由不能小于10个字'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message'=>'参数验证失败',
                'errors' => $validator->errors(),
            ]);
        }
        $Registration=ApplicationForm::create([
            'config_id'=>request('config_id'),
            'class'=>request('class'),
            'academy'=>request('academy'),
            'major'=>request('major'),
            'director_name'=>request('director_name'),
            'sign_reason'=>request('sign_reason'),
        ]);



    }


}
