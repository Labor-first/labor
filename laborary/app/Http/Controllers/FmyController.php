<?php

namespace app\Http\Controllers;

use App\Models\ApplicationForm;
use App\Models\RegistrationConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;
use Throwable; // 用于捕获所有错误（包括系统级错误）
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\UserNotDefinedException;
use Tymon\JWTAuth\Facades\JWTAuth;

class FmyController extends Controller
{
    // 获取个人信息
    public function me(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '用户未找到',
                    'data' => null,
                ], 401);
            }

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->username, // 确保数据库字段名正确
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role' => $user->role,
                    'department_id' => $user->department, // 确认字段名是 department 还是 department_id
                    'last_login_at' => $user->last_login_at,
                ]
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json(['code' => 401, 'message' => 'Token 已过期', 'data' => null], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['code' => 401, 'message' => 'Token 无效', 'data' => null], 401);
        } catch (UserNotDefinedException $e) {
            return response()->json(['code' => 401, 'message' => '用户不存在', 'data' => null], 401);
        } catch (Throwable $e) {
            return response()->json(['code' => 500, 'message' => '服务器内部错误: ' . $e->getMessage(), 'data' => null], 500);
        }
    }

    //修改密码
    public function changePassword(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['code' => 401, 'message' => '用户未找到', 'data' => null], 401);
            }

            $validator = Validator::make(request()->all(), [
                'old_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:6|confirmed', // confirmed 意味着需要 new_password_confirmation 字段
            ]);

            if ($validator->fails()) {
                // 4. 验证失败返回 422
                return response()->json([
                    'code' => 422,
                    'message' => '参数验证失败',
                    'data' => null,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // 验证旧密码
            if (!Hash::check($data['old_password'], $user->password)) {
                // 5. 业务逻辑错误返回 400 或 403，不要用 401
                return response()->json([
                    'code' => 400,
                    'message' => '旧密码错误',
                    'data' => null
                ], 400);
            }

            // 更新密码
            $user->password = Hash::make($data['new_password']);
            $user->save();

            // 生成新 Token (可选策略：也可以选择不返回新 Token，强制用户重新登录以销毁所有旧会话)
            $newToken = JWTAuth::fromUser($user);

            return response()->json([
                'code' => 200,
                'message' => '密码修改成功',
                'data' => null,
                'token' => $newToken,
            ]);

        } catch (TokenExpiredException $e) {
            return response()->json(['code' => 401, 'message' => 'Token 已过期', 'data' => null], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['code' => 401, 'message' => 'Token 无效', 'data' => null], 401);
        } catch (UserNotDefinedException $e) {
            return response()->json(['code' => 401, 'message' => '用户不存在', 'data' => null], 401);
        } catch (Throwable $e) {
            // 捕获所有其他未知错误
            return response()->json([
                'code' => 500,
                'message' => '修改失败，服务器内部出错',
                'data' => null,
            ], 500);
        }
    }

    // 提交报名
    public function registrationStore(): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '用户未找到或令牌无效',
                    'data' => null], 401);
            }

            $validator = Validator::make(request()->all(), [
                'config_id'     => 'required|integer|exists:registration_configs,id',
                'class'         => 'required|integer',
                'academy'       => 'required|string|max:100',
                'major'         => 'required|string|max:100',
                'director_name' => 'required|string|max:50',
                'sign_reason'   => 'required|string|min:10|max:500'
            ], [
                'config_id.required'     => '配置ID不能为空',
                'config_id.exists'       => '无效的报名配置',
                'class.required'         => '班级不能为空',
                'academy.required'       => '学院不能为空',
                'major.required'         => '专业不能为空',
                'director_name.required' => '导员姓名不能为空',
                'sign_reason.required'   => '申请理由不能为空',
                'sign_reason.min'        => '申请理由不能少于10个字',
                'sign_reason.max'        => '申请理由不能超过500个字',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422, // 修正为 422
                    'message' => '参数验证失败',
                    'data' => null,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            $registration = DB::transaction(function () use ($user, $data) {
                $config = RegistrationConfig::where('id', $data['config_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$config) {
                    throw new Exception('报名配置不存在');
                }

                if ($config->is_open != 1) {
                    throw new Exception('报名通道已关闭');
                }

                $exists = ApplicationForm::where('config_id', $data['config_id'])
                    ->where('user_id', $user->id)
                    ->exists();

                if ($exists) {
                    throw new Exception('您已报名，请勿重复提交');
                }

                $createData = array_merge($data, [
                    'user_id' => $user->id,
                    'status'  => 1,
                ]);

                return ApplicationForm::create($createData);
            });

            return response()->json([
                'code' => 200,
                'message' => '报名成功',
                'data' => $registration
            ], 201);

        } catch (Exception $e) {
            // 捕获业务逻辑异常
            $msg = $e->getMessage();
            $statusCode = 400;

            if (str_contains($msg, '已关闭')) $statusCode = 403;
            if (str_contains($msg, '重复')) $statusCode = 403;
            if (str_contains($msg, '不存在')) $statusCode = 404;

            return response()->json([
                'code' => $statusCode,
                'message' => $msg,
                'data' => null,
            ], $statusCode);

        } catch (Throwable $e) {
            // 6. 捕获系统级异常 (如数据库死锁)
            // 建议这里记录日志 Log::error($e);
            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，请稍后重试',
                'data' => null,
            ], 500);
        }
    }

    //查看报名状态
    public function getRegistrationStatus(): JsonResponse
    {
        try {
            // 1. 用户认证
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['code' => 401, 'message' => '用户未找到', 'data' => null], 401);
            }

            // 2. 验证参数
            $validator = Validator::make(request()->all(), [
                'config_id' => 'required|integer|exists:registration_configs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'message' => '参数验证失败',
                    'data' => null,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $configId = $validator->validated()['config_id'];

            // 3. 查询报名记录 (只查当前用户的)
            $registration = ApplicationForm::where('config_id', $configId)
                ->where('user_id', $user->id)
                // 如果做了软删除，加上 .withTrashed() 根据需要
                ->first();

            if (!$registration) {
                return response()->json([
                    'code' => 404,
                    'message' => '您尚未报名',
                    'data' => ['has_registered' => false]
                ], 404);
            }

            // 定义状态映射 (根据你数据库的实际状态值调整)
            $statusMap = [
                1 => '待审核',
                2 => '审核通过',
                3 => '审核拒绝',
                4 => '已取消',
            ];

            return response()->json([
                'code' => 200,
                'message' => '查询成功',
                'data' => [
                    'has_registered' => true,
                    'id' => $registration->id,
                    'status' => $registration->status,
                    'status_text' => $statusMap[$registration->status] ?? '未知状态',
                    'created_at' => $registration->created_at,
                    'can_cancel' => $registration->status == 1, // 只有待审核状态允许取消
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => '查询失败: ' . $e->getMessage(), 'data' => null], 500);
        }
    }

    //撤销报名
    public function cancelRegistration(): JsonResponse
    {
        try {
            // 1. 用户认证
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['code' => 401, 'message' => '用户未找到', 'data' => null], 401);
            }

            // 2. 验证参数
            $validator = Validator::make(request()->all(), [
                'config_id' => 'required|integer|exists:registration_configs,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'message' => '参数验证失败',
                    'data' => null,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $configId = $data['config_id'];

            // 3. 开启事务 (涉及状态变更和可能的名额回滚，必须原子化)
            $result = DB::transaction(function () use ($user, $configId) {

                // 4. 锁定并查询报名记录 (lockForUpdate 防止并发撤销导致状态错乱)
                $registration = ApplicationForm::where('config_id', $configId)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$registration) {
                    throw new Exception('未找到报名记录');
                }

                // 5. 状态检查：只有“待审核”(假设 status=1) 可以取消
                // 如果业务允许“审核通过”也能取消，请修改此条件
                if ($registration->status != 1) {
                    throw new Exception('当前状态（' . ($registration->status == 2 ? '已通过' : '已拒绝/已取消') . '）无法撤销报名');
                }

                // 6. 执行撤销逻辑

                // 方案 A: 仅更新状态为“已取消” (推荐，保留数据痕迹)
                $registration->status = 4; // 假设 4 代表已取消
                $registration->save();

                // 方案 B: 如果配置表里有“当前报名人数”字段，需要减 1 (防止超卖的反向操作)
                // 注意：这里也需要锁住 config 表，或者依赖上面的 registration 锁间接保证安全
                // 如果你的 registration_configs 表有 current_count 字段：
                /*
                $config = RegistrationConfig::where('id', $configId)->lockForUpdate()->first();
                if ($config && $config->current_count > 0) {
                    $config->decrement('current_count');
                }
                */

                return true;
            });

            return response()->json([
                'code' => 200,
                'message' => '撤销报名成功',
                'data' => ['status' => 'cancelled']
            ]);

        } catch (Exception $e) {
            // 捕获业务逻辑异常
            $msg = $e->getMessage();
            $statusCode = 400;
            if (str_contains($msg, '未找到')) $statusCode = 404;
            if (str_contains($msg, '无法撤销')) $statusCode = 403; // 禁止操作

            return response()->json([
                'code' => $statusCode,
                'message' => $msg,
                'data' => null,
            ], $statusCode);

        } catch (\Throwable $e) {
            // 捕获系统级异常
            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，撤销失败',
                'data' => null,
            ], 500);
        }
    }
}
