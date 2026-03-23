<?php

namespace App\Http\Controllers;

// 引入 4 个 FormRequest 进行自动验证
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\CheckRegistrationStatusRequest;
use App\Http\Requests\CancelRegistrationRequest;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use App\Models\RegistrationConfig;
use App\Models\LabUser; // 假设你的用户模型是 LabUser

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;
use Illuminate\Support\Facades\Auth; // 用于 Sanctum 认证

class FmyController extends Controller
{
    /**
     * 辅助方法：通过学号和姓名验证用户身份 (免登录场景专用)
     * 用于报名、查询、撤销等不需要 Token 的公开接口
     */
    private function verifyStudentCredentials()
    {
        $request = request();
        $studentAccount = $request->input('user_id');
        $username = $request->input('name');

        // 基础非空检查 (虽然 FormRequest 可能已校验，但为了双重保险保留)
        if (!$studentAccount || !$username) {
            return [
                'success' => false,
                'response' => response()->json([
                    'code' => 401,
                    'message' => '缺少必要参数：需要提供 account (学号) 和 username (姓名)',
                    'data' => null
                ], 401)
            ];
        }

        // 查询用户
        // 注意：请确保数据库字段名与这里一致 (account, username)
        $user = LabUser::where('account', $studentAccount)
            ->where('username', $username)
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'response' => response()->json([
                    'code' => 401,
                    'message' => '学号与姓名不匹配，或未找到该用户',
                    'data' => null
                ], 401)
            ];
        }

        // 检查账号是否被禁用 (is_active == 0)
        if (isset($user->is_active) && $user->is_active == 0) {
            return [
                'success' => false,
                'response' => response()->json([
                    'code' => 403,
                    'message' => '该账号未激活',
                    'data' => null
                ], 403)
            ];
        }

        return ['success' => true, 'user' => $user];
    }

    /**
     * 获取当前登录用户个人信息 (Sanctum 保护)
     */
    public function me(): JsonResponse
    {
        // Sanctum: 获取当前认证用户
        /** @var \App\Models\LabUser $user */ //告诉IDE:“这个$user具体就是我的LabUser模型”
        $user = Auth::guard('api')->user();
        // 或者简写为: $user = auth('sanctum')->user();
        // 如果中间件已生效，也可以直接用 $user = request()->user();

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '用户未登录或认证失败',
                'data' => null
            ], 401);
        }

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'id' => $user->id,
                'account' => $user->account, // 兼容字段
                'username' => $user->username,
                'phone' => $user->phone,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'role' => $user->role,
                'department_id' => $user->department_id,
                'last_login_at' => $user->last_login_at,
            ]
        ]);
    }

    /**
     * 修改密码 (Sanctum 保护 + ChangePasswordRequest 验证)
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        // 获取当前用户 (Sanctum)
        /** @var \App\Models\LabUser $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '用户未找到',
                'data' => null
            ], 401);
        }

        //获取已验证的数据 (FormRequest 自动处理了验证失败，能到这里说明数据合法)
        $data = $request->validated();

        //验证旧密码
        if (!Hash::check($data['old_password'], $user->password)) {
            return response()->json([
                'code' => 400,
                'message' => '旧密码错误',
                'data' => null
            ], 400);
        }

        //更新密码
        $user->password = Hash::make($data['new_password']);
        $user->save();

        // Sanctum 不需要重新颁发 Token (除非你想让旧 Token 失效，那需要额外逻辑)
        // 通常前端保留当前Token即可，或者前端选择重新登录

        return response()->json([
            'code' => 200,
            'message' => '密码修改成功',
            'data' => null
        ]);
    }


    /**
     * 提交报名 (免登录 + StoreRegistrationRequest 验证)
     */
    public function registrationStore(StoreRegistrationRequest $request): JsonResponse
    {
        //身份业务验证 (学号 + 姓名)
        $validation = $this->verifyStudentCredentials();
        if (!$validation['success']) {
            return $validation['response'];
        }
        $user = $validation['user'];

        //获取已验证的数据 (由 StoreRegistrationRequest 保证)
        $data = $request->validated();

        try {
            $registration = DB::transaction(function () use ($user, $data) {
                // 锁行防止超卖或并发问题
                $config = RegistrationConfig::where('id', $data['config_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$config) {
                    throw new Exception('报名配置不存在', 404);
                }
                if ($config->is_open != 1) {
                    throw new Exception('报名通道已关闭', 403);
                }

                $exists = ApplicationForm::where('config_id', $data['config_id'])
                    ->where('user_id', $user->account)
                    ->exists();

                if ($exists) {
                    throw new Exception('您已报名，请勿重复提交', 409);
                }

                $applicationForm = ApplicationForm::create([
                    'config_id'     => $data['config_id'],
                    'name'          => $data['name'],
                    'class'         => $data['class'],
                    'academy'       => $data['academy'],
                    'major'         => $data['major'],
                    'director_name' => $data['director_name'],
                    'sign_reason'   => $data['sign_reason'],
                    'user_id'       => $user->account,
                    'status'        => 1,
                ]);

                // 验证数据是否真正存入数据库
                if (!$applicationForm || !$applicationForm->id) {
                    throw new Exception('报名数据保存失败，请稍后重试', 500);
                }

                return $applicationForm;
            });

            // 再次验证数据是否存在
            $verifyRecord = ApplicationForm::where('id', $registration->id)->first();
            if (!$verifyRecord) {
                return response()->json([
                    'code' => 500,
                    'message' => '报名数据保存异常，请稍后重试',
                    'data' => null
                ], 500);
            }

            return response()->json([
                'code' => 201,
                'message' => '报名成功',
                'data' => $registration
            ], 201);

        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            
            // 根据错误信息智能判断状态码
            $msg = $e->getMessage();
            $statusCode = match(true) {
                str_contains($msg, '已关闭') => 403,
                str_contains($msg, '重复') => 409,
                str_contains($msg, '不存在') => 404,
                str_contains($msg, '保存失败') => 500,
                str_contains($msg, '保存异常') => 500,
                default => $statusCode
            };

            Log::error('Registration Error: ' . $msg, [
                'user_id' => $user->account ?? null,
                'config_id' => $data['config_id'] ?? null,
                'status_code' => $statusCode
            ]);

            return response()->json([
                'code' => $statusCode,
                'message' => $msg,
                'data' => null
            ], $statusCode);
        } catch (Throwable $e) {
            Log::error('Registration System Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，请稍后重试',
                'data' => null
            ], 500);
        }
    }

    /**
     * 查看报名状态 (免登录 + CheckRegistrationStatusRequest 验证)
     */
    public function CheckRegistrationStatus(CheckRegistrationStatusRequest $request): JsonResponse
    {
        //身份业务验证
        $validation = $this->verifyStudentCredentials();
        if (!$validation['success']) {
            return $validation['response'];
        }
        $user = $validation['user'];

        //获取 config_id (由 FormRequest 验证过)
        $configId = $request->validated()['config_id'];

        $registration = ApplicationForm::where('config_id', $configId)
            ->where('user_id', $user->account)
            ->first();

        if (!$registration) {
            return response()->json([
                'code' => 404,
                'message' => '您尚未报名',
                'data' => null
            ], 404);
        }

        $statusMap = [1 => '待审核', 2 => '审核通过', 3 => '已取消', 4 => '审核拒绝'];

        return response()->json([
            'code' => 200,
            'message' => '查询成功',
            'data' => [
                'id' => $registration->id,
                'status' => $registration->status,
                'status_text' => $statusMap[$registration->status]
            ]
        ]);
    }

    /**
     * 撤销报名 (免登录 + CancelRegistrationRequest 验证)
     */
    public function cancelRegistration(CancelRegistrationRequest $request): JsonResponse
    {
        //身份业务验证
        $validation = $this->verifyStudentCredentials();
        if (!$validation['success']) {
            return $validation['response'];
        }
        $user = $validation['user'];

        //获取 config_id
        $configId = $request->validated()['config_id'];

        try {
            $cancelled = DB::transaction(function () use ($user, $configId) {
                $registration = ApplicationForm::where('config_id', $configId)
                    ->where('user_id', $user->account)
                    ->lockForUpdate()
                    ->first();

                if (!$registration) {
                    throw new Exception('未找到报名记录', 404);
                }

                if ($registration->status != 1) {
                    $statusText = match($registration->status) {
                        2 => '已通过',
                        3 => '已取消',
                        4 => '已拒绝',
                        default => '未知'
                    };
                    throw new Exception("当前状态（{$statusText}）无法撤销报名", 403);
                }

                $registration->status = 3;
                $saved = $registration->save();

                if (!$saved) {
                    throw new Exception('撤销报名保存失败，请稍后重试', 500);
                }

                return $registration;
            });

            // 验证撤销是否成功
            $verifyRecord = ApplicationForm::where('id', $cancelled->id)
                ->where('status', 3)
                ->first();

            if (!$verifyRecord) {
                return response()->json([
                    'code' => 500,
                    'message' => '撤销报名验证失败，请稍后重试',
                    'data' => null
                ], 500);
            }

            return response()->json([
                'code' => 200,
                'message' => '撤销报名成功',
                'data' => [
                    'id' => $cancelled->id,
                    'status' => 3,
                    'status_text' => '已取消'
                ]
            ]);

        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            
            $msg = $e->getMessage();
            $statusCode = match(true) {
                str_contains($msg, '未找到') => 404,
                str_contains($msg, '无法撤销') => 403,
                str_contains($msg, '保存失败') => 500,
                str_contains($msg, '验证失败') => 500,
                default => $statusCode
            };

            Log::error('Cancel Registration Error: ' . $msg, [
                'user_id' => $user->account ?? null,
                'config_id' => $configId ?? null,
                'status_code' => $statusCode
            ]);

            return response()->json([
                'code' => $statusCode,
                'message' => $msg,
                'data' => null
            ], $statusCode);
        } catch (Throwable $e) {
            Log::error('Cancel Registration System Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，撤销失败',
                'data' => null
            ], 500);
        }
    }
}
