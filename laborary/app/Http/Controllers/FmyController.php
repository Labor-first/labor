<?php

namespace App\Http\Controllers;

// 引入 4 个 FormRequest 进行自动验证
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\CheckRegistrationStatusRequest;
use App\Http\Requests\CancelRegistrationRequest;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingNotificationRequest;
use App\Models\ApplicationForm;
use App\Models\Homework;
use App\Models\RegistrationConfig;
use App\Models\LabUser;
use App\Models\TrainingNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Request;
use Throwable;
use Illuminate\Support\Facades\Auth; // 用于 Sanctum 认证
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordCode;
use App\Mail\TrainingNotificationMail;

class FmyController extends Controller
{
    /**
     * 辅助方法：通过学号和姓名验证用户身份 (免登录场景专用)
     * 用于报名、查询、撤销等不需要 Token 的公开接口
     */
    private function verifyStudentCredentials()
    {
        //request():这是Laravel的全局辅助函数，用于获取当前的HTTP请求实例（Illuminate\Http\Request 对象）
        $request = request();
        $studentAccount = $request->input('user_id');//从请求中获取指定键名的值。
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
        if (!Hash::check($data['old_password'], $user->password_hash)) {
            return response()->json([
                'code' => 400,
                'message' => '旧密码错误',
                'data' => null
            ], 400);
        }

        //更新密码
        $user->password_hash = Hash::make($data['new_password']);
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
     * 发送重置密码验证码 (免登录)
     * POST /api/forgot-password/send-code
     */
    public function sendResetCode(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'required|string',
            'email' => 'required|email'
        ]);

        $user = LabUser::where('account', $request->account)
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => '账号与邮箱不匹配，或用户不存在',
                'data' => null
            ], 404);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $cacheKey = "reset_code:{$user->account}";
        Cache::put($cacheKey, [
            'code' => $code,
            'email' => $user->email,
            'attempts' => 0
        ], now()->addMinutes(10));

        try {
            Mail::to($user->email)->send(new ResetPasswordCode($code, $user->username));

            return response()->json([
                'code' => 200,
                'message' => '验证码已发送到您的邮箱，有效期10分钟',
                'data' => [
                    'email' => substr_replace($user->email, '****', 2, strpos($user->email, '@') - 2)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Send Reset Code Error: ' . $e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => '验证码发送失败，请稍后重试',
                'data' => null
            ], 500);
        }
    }

    /**
     * 验证验证码并重置密码 (免登录)
     * POST /api/forgot-password/reset
     */
    public function resetPassword(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'required|string',
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        $user = LabUser::where('account', $request->account)
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => '账号与邮箱不匹配，或用户不存在',
                'data' => null
            ], 404);
        }

        $cacheKey = "reset_code:{$user->account}";
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            return response()->json([
                'code' => 400,
                'message' => '验证码已过期，请重新获取',
                'data' => null
            ], 400);
        }

        if ($cachedData['attempts'] >= 5) {
            Cache::forget($cacheKey);
            return response()->json([
                'code' => 429,
                'message' => '验证码错误次数过多，请重新获取',
                'data' => null
            ], 429);
        }

        if ($cachedData['code'] !== $request->code) {
            $cachedData['attempts']++;
            Cache::put($cacheKey, $cachedData, now()->addMinutes(10));

            return response()->json([
                'code' => 400,
                'message' => '验证码错误，请重新输入',
                'data' => [
                    'remaining_attempts' => 5 - $cachedData['attempts']
                ]
            ], 400);
        }

        $user->password_hash = Hash::make($request->new_password);
        $user->save();

        Cache::forget($cacheKey);

        Log::info('Password Reset Success', [
            'account' => $user->account,
            'email' => $user->email,
            'ip' => $request->ip()
        ]);

        return response()->json([
            'code' => 200,
            'message' => '密码重置成功，请使用新密码登录',
            'data' => null
        ]);
    }


    /**
     * 提交报名 (免登录 + StoreRegistrationRequest 验证)
     */
    public function registrationStore(StoreRegistrationRequest $request): JsonResponse
    {
        //获取已验证的数据 (由 StoreRegistrationRequest 保证)
        $data = $request->validated();

        try {
            $registration = DB::transaction(function () use ($data) {
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
                    ->where('user_id', $data['user_id'])
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
                    'email'         => $data['email'],
                    'director_name' => $data['director_name'],
                    'sign_reason'   => $data['sign_reason'],
                    'user_id'       => $data['user_id'],
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
        //获取已验证的数据 (由 StoreRegistrationRequest 保证)
        $registration = $request->validated();


        $registration = ApplicationForm::where('user_id',$registration['user_id'] )
            ->where('name', $registration['name'] )
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
        //获取已验证的数据 (由 StoreRegistrationRequest 保证)
        $data = $request->validated();

        try {
            $cancelled = DB::transaction(function () use ($data) {
                $registration = ApplicationForm::where('user_id', $data['user_id'])
                    ->where('name', $data['name'])
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














    /**
     * 发布/保存培训通知
     */
    public function store(StoreTrainingNotificationRequest $request): JsonResponse
    {
        // 检查是否登录
        if (!Auth::check()) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ], 401);
        }

        $data = $request->validated();

        try {
            $sendTimeType = $data['send_time_type'];
            $scheduledTime = $data['scheduled_time']??null;
            $isDraft = $data['is_draft'];

            // 验证：立即发送时不能为草稿
            if ($sendTimeType === 'immediate' && $isDraft) {
                return response()->json([
                    'code' => 400,
                    'message' => '立即发送时不能保存为草稿',
                    'data' => null
                ], 400);
            }
            // 验证定时发送时间
            if ($sendTimeType === 'scheduled') {
                if (empty($scheduledTime)) {
                    return response()->json([
                        'code' => 400,
                        'message' => '定时发送时间不能为空',
                        'data' => null
                    ], 400);
                }

                $scheduledTimestamp = strtotime($scheduledTime);
                // 使用北京时间（东八区）
                $currentTime = strtotime(now()->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s'));

                // 调试日志
                Log::info('定时时间验证', [
                    'scheduledTime' => $scheduledTime,
                    'scheduledTimestamp' => $scheduledTimestamp,
                    'currentTime' => $currentTime,
                    'currentTimeBeijing' => now()->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s'),
                    'sendTimeType' => $sendTimeType
                ]);

                if ($scheduledTimestamp === false) {
                    return response()->json([
                        'code' => 400,
                        'message' => '定时发送时间格式无效',
                        'data' => null
                    ], 400);
                }

                if ($scheduledTimestamp <= $currentTime) {
                    return response()->json([
                        'code' => 400,
                        'message' => '定时发送时间必须大于当前时间',
                        'data' => [
                            'scheduled_time' => $scheduledTime,
                            'scheduled_timestamp' => $scheduledTimestamp,
                            'current_time' => date('Y-m-d H:i:s', $currentTime)
                        ]
                    ], 400);
                }
            }

            // 系统生成 status
            $status = $isDraft ? 'draft' : ($sendTimeType === 'immediate' ? 'sent' : 'pending');

            // 获取当前登录用户ID
            $userId = Auth::guard('api')->user()->id;

            // 创建通知
            $notification = TrainingNotification::create([
                'title' => $data['title'],
                'content' => $data['content'],
                'target' => $data['target'],
                'target_ids' => $data['target_ids'] ?? null,
                'send_time_type' => $sendTimeType,
                'scheduled_time' => $sendTimeType === 'scheduled' ? $scheduledTime : null,
                'is_draft' => $isDraft,
                'status' => $status,
                'created_id' => $userId,
                'sent_at' => null, // 发送成功后再更新
            ]);


            // 不是草稿 → 直接发送
            if (!$isDraft) {
                try {
                    // 执行真正发送
                    $this->sendNotification($notification);

                    // 发送成功 → 更新状态和发送时间
                    $notification->update([
                        'status' => 'sent',
                        'sent_at' => now()
                    ]);

                } catch (\Exception $e) {
                    // 发送失败 → 更新状态
                    $notification->update(['status' => 'failed']);

                    Log::error('培训通知发送失败', [
                        'id' => $notification->id,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'code' => 500,
                        'message' => '通知保存成功，但发送失败，请稍后重试',
                        'data' => [
                            'id' => $notification->id,
                            'status' => 'failed'
                        ]
                    ], 500);
                }
            }

            $message = $isDraft ? '草稿保存成功' :
                ($sendTimeType === 'immediate' ? '通知发送成功' : '定时发送设置成功');

            return response()->json([
                'code' => 200,
                'message' => $message,
                'data' => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'status' => $notification->status,
                    'send_time_type' => $notification->send_time_type,
                    'scheduled_time' => $notification->scheduled_time,
                    'sent_at' => $notification->sent_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('保存培训通知失败', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return response()->json([
                'code' => 500,
                'message' => '操作失败，请稍后重试',
                'data' => null
            ], 500);
        }
    }

    /**
     * 真正发送通知（支持：all全部 / week指定周次 / trainee指定学员）
     */
    private function sendNotification(TrainingNotification $notification): void
    {
        $title = $notification->title;
        $content = $notification->content;
        $target = $notification->target;
        $targetIds = $notification->target_ids;

        Log::info('【正式发送培训通知】', [
            '通知ID' => $notification->id,
            '标题' => $title,
            '目标类型' => $target,
            '目标ID' => $targetIds,
        ]);

        $users = collect();

        switch ($target) {
            case 'all':
                $users = ApplicationForm::select('id', 'username', 'email')
                    ->whereNotNull('email')
                    ->get();
                break;

            case 'week':
                if (empty($targetIds)) break;
                $users = Homework::whereIn('week', $targetIds)
                    ->join('lab_users', 'homework.user_id', '=', 'lab_users.id')
                    ->select('lab_users.id', 'lab_users.name', 'lab_users.email')
                    ->whereNotNull('lab_users.email')
                    ->distinct()
                    ->get();
                break;

            case 'trainee':
                if (empty($targetIds)) break;
                $users = ApplicationForm::whereIn('id', $targetIds)
                    ->select('id', 'name', 'email')
                    ->whereNotNull('email')
                    ->get();
                break;

            default:
                throw new \Exception('不支持的发送目标类型: ' . $target);
        }

        if ($users->isEmpty()) {
            throw new \Exception('未找到任何接收用户');
        }

        // 发送邮件给每个用户
        $successCount = 0;
        $failCount = 0;

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new TrainingNotificationMail($title, $content));
                $successCount++;
                Log::info('邮件发送成功', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'notification_id' => $notification->id
                ]);
            } catch (\Exception $e) {
                $failCount++;
                Log::error('邮件发送失败', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('培训通知发送完成', [
            'notification_id' => $notification->id,
            '总用户数' => $users->count(),
            '成功' => $successCount,
            '失败' => $failCount
        ]);

        // 如果全部失败，抛出异常
        if ($failCount === $users->count()) {
            throw new \Exception('所有邮件发送失败');
        }
    }
        /**
     * 获取当前登录用户未发布的草稿
     */
    public function getDrafts(Request $request): JsonResponse
    {
        // 检查是否登录
        if (!Auth::check()) {
            return response()->json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null
            ], 401);
        }

        try {
            $userId = Auth::guard('api')->user()->id;
            $drafts = TrainingNotification::where('created_id', $userId)
                ->where('is_draft', true)
                ->where('status', 'draft')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'code' => 200,
                'message' => '获取草稿列表成功',
                'data' => $drafts
            ]);

        } catch (\Exception $e) {
            Log::error('获取草稿列表失败', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'code' => 500,
                'message' => '获取草稿列表失败，请稍后重试',
                'data' => null
            ], 500);
        }
    }

    //获取个人作业任务列表
    public function getTaskList(Request $request): JsonResponse
    {
        
    }
}
