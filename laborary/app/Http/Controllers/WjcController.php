<?php

namespace App\Http\Controllers;

use App\Models\LabUser;
use App\Models\HomeworkTask;
use App\Models\HomeworkSubmission;      
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\SendActivationCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;


class WjcController extends Controller
{
    public function login(\Illuminate\Http\Request $request): JsonResponse
    {
        $credentials = $request->only('account', 'password');
        $remember = $request->input('remember_me', false);
        
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'code' => 401,
                'msg' => '账号密码错误或账号不存在',
                'data' => null
            ]);
        }
        
        $user = JWTAuth::user();
        
        // 检查用户是否已激活
        if (!$user->is_active) {
            // 用户未激活，直接返回错误，不处理 token
            // 因为登录时生成的临时 token 不需要特别注销
            return response()->json([
                'code' => 403,
                'msg' => '账号未激活，请先验证激活码',
                'data' => [
                    'need_activation' => true,
                    'account' => $user->account
                ]
            ]);
        }
        
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
    // 参数验证
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

    // 频率限制：先判断过期时间不为null，再判断时间范围（修复空对象调用问题）
    if($user->activation_expire && $user->activation_expire->isAfter(now()->subMinute(1))){
        return response()->json([
            'code' => 429,
            'msg' => '请勿频繁获取激活码，1分钟后重试',
            'data' => null
        ]);
    }
    
    // 生成6位数字激活码（mt_rand 兼容所有Laravel版本）
    $activationCode = mt_rand(100000, 999999);
    $expireMinutes = 30; // 明确定义有效期，方便维护
    $expireTime = now()->addMinutes($expireMinutes);

    // 保存激活码到数据库（加try-catch提升健壮性）
    try {
        $user->activation_code = $activationCode;
        $user->activation_expire = $expireTime;
        $user->save();
    } catch (\Exception $e) {
        Log::error('激活码保存失败', ['account' => $account, 'error' => $e->getMessage()]);
        return response()->json([
            'code' => 500,
            'msg' => '激活码保存失败，请重试',
            'data' => null
        ]);
    }

    // 发送激活码邮件
    try{
        // 修正：1. 传account而非username 2. 补充有效期参数expireMinutes
        Mail::to($user->email)->send(new SendActivationCodeMail($user->account, $activationCode, $expireMinutes));
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
            $user = auth('api')->user();
            
            // 将用户标记为未激活，下次登录需要重新验证激活码
            if ($user) {
                $user->is_active = 0;
                LabUser::where('id', $user->id)->update(['is_active' => 0]);
            }
            
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

    //培训统计
    public function stats()
    {
        return response()->json([
            'code' => 200,
            'msg'  => '操作成功',
            'data' => [
                'totalTrainees'     => 0,
                'weekSubmissions'   => 0,
                'pendingCorrection' => 0,
                'sentNotifications' => 0,
            ]
        ]);
    }

    // 学员学习进度列表
    public function learnProgress(Request $request)
    {
        return response()->json([
            'code' => 200,
            'msg'  => '操作成功',
            'data' => [
                [
                    'traineeId'      => 1,
                    'traineeName'    => "测试学员",
                    'currentWeek'    => '第3周',
                    'completionRate' => 80,
                    'homeworkStatus' => 'unsubmitted',
                ]
            ]
        ]);
    }

    //发布作业（管理员）
    public function publishTask(Request $request)
    {
        // 检查是否登录且为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限',
                'data' => null
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'attachment' => 'nullable|string',
            'deadline' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $task = HomeworkTask::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'attachment' => $request->input('attachment'),
            'deadline' => $request->input('deadline'),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'code' => 200,
            'msg' => '作业发布成功',
            'data' => [
                'taskId' => (string) $task->id,
                'title' => $task->title,
                'publishTime' => $task->created_at,
            ]
        ]);
    }

    // 待批改作业队列
    public function pendingCorrection()
    {
        return response()->json([
            'code' => 200,
            'msg'  => '操作成功',
            'data' => []
        ]);
    }

    // 作业提交详情
    public function homeworkDetail($submissionId)
    {
        $submission = HomeworkSubmission::with(['user:id,username', 'task'])->find($submissionId);

        if (!$submission) {
            return response()->json([
                'code' => 404,
                'msg'  => '作业提交不存在',
                'data' => null
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'msg'  => '操作成功',
            'data' => [
                'submissionId' => $submission->id,
                'taskId'       => $submission->task_id,
                'taskTitle'    => $submission->task ? $submission->task->title : null,
                'traineeName'  => $submission->user ? $submission->user->username : null,
                'content'      => $submission->content,
                'attachment'   => $submission->attachment,
                'status'       => $submission->status,
                'score'        => $submission->score,
                'comment'      => $submission->comment,
                'submittedAt'  => $submission->created_at,
            ]
        ]);
    }

    // 提交批改
    public function correctHomework(Request $request, $submissionId)
    {
        $submission = HomeworkSubmission::find($submissionId);

        if (!$submission) {
            return response()->json([
                'code' => 404,
                'msg'  => '作业提交不存在',
                'data' => null
            ], 404);
        }

        // 检查是否登录且为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg'  => '需要管理员权限',
                'data' => null
            ], 403);
        }

        // 验证请求数据
        $validator = Validator::make($request->all(), [
            'score'   => 'required|integer|min:0|max:100',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg'  => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        // 更新作业批改信息
        $submission->correct($request->input('score'), $request->input('comment'));

        return response()->json([
            'code' => 200,
            'msg'  => '批改成功',
            'data' => [
                'submissionId' => $submission->id,
                'score'        => $submission->score,
                'comment'      => $submission->comment,
                'status'       => $submission->status,
            ]
        ]);
    }

    //查看个人作业批改情况
    public function getTaskCorrectInfo($taskId)
    {
        $task = HomeworkTask::find($taskId);
        if(!$task){
            return response()->json([
                'code' => 404,
                'msg' => '作业任务不存在',
                'data' => null,
                'timestamp' => time()
            ]);
        }

        // 获取当前用户的提交记录
        $user = request()->user();
        $submission = HomeworkSubmission::where('task_id', $taskId)
            ->where('user_id', $user ? $user->id : 0)
            ->first();

        return response()->json([
            'code' => 200,
            'msg' => '查询成功',
            'data' => [
                'taskId' => $taskId,
                'correctStatus' => $submission ? $submission->status : 'unsubmitted',
                'correctScore'    => $submission ? $submission->score : null,
                'correctComment'  => $submission ? $submission->comment : null,
                'correctTime'     => $submission && $submission->updated_at ? $submission->updated_at->format('Y-m-d H:i:s') : null,
            ],
            'timestamp' => time()
        ]);
    }

    //作业任务回显
    public function echoTask($taskId)
    {
        $task = HomeworkTask::find($taskId);
        if (!$task) {
            return response()->json([
                'code' => 404,
                'msg'  => '未查询到该作业任务信息，无法回显',
                'data' => null,
                'timestamp' => time()
            ]);
        }
        return response()->json([
            'code' => 200,
            'msg'  => '作业回显成功',
            'data' => [
                'taskId'          => $taskId,
                'taskName'        => $task->title,
                'taskDesc'        => $task->content, 
                'createTime'      => $task->created_at->format('Y-m-d H:i:s'),
                'deadline'        => $task->deadline ? $task->deadline->format('Y-m-d H:i:s') : null,
                'attachment'      => $task->attachment,
            ],
            'timestamp' => time()
        ]);
    }

    // 学员提交作业
    public function submitHomework(Request $request, $taskId)
    {
        // 检查是否登录
        if (!$request->user()) {
            return response()->json([
                'code' => 401,
                'msg'  => '请先登录',
                'data' => null
            ], 401);
        }

        // 检查作业任务是否存在
        $task = HomeworkTask::find($taskId);
        if (!$task) {
            return response()->json([
                'code' => 404,
                'msg'  => '作业任务不存在',
                'data' => null
            ], 404);
        }

        // 检查是否已截止
        if ($task->isExpired()) {
            return response()->json([
                'code' => 403,
                'msg'  => '作业已截止提交',
                'data' => null
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content'    => 'required|string',
            'attachment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg'  => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $user = $request->user();

        // 检查是否已提交过
        $existingSubmission = HomeworkSubmission::where('task_id', $taskId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingSubmission) {
            // 更新已有提交
            $existingSubmission->update([
                'content'    => $request->input('content'),
                'attachment' => $request->input('attachment'),
                'status'     => 'submitted',
            ]);
            $submission = $existingSubmission;
            $msg = '作业更新成功';
        } else {
            // 创建新提交
            $submission = HomeworkSubmission::create([
                'task_id'    => $taskId,
                'user_id'    => $user->id,
                'content'    => $request->input('content'),
                'attachment' => $request->input('attachment'),
                'status'     => 'submitted',
            ]);
            $msg = '作业提交成功';
        }

        return response()->json([
            'code' => 200,
            'msg'  => $msg,
            'data' => [
                'submissionId' => $submission->id,
                'taskId'       => $taskId,
                'status'       => $submission->status,
                'submittedAt'  => $submission->created_at,
            ]
        ]);
    }

    // 获取作业任务列表
    public function getHomeworkTaskList(Request $request)
    {
        // 检查是否登录
        if (!$request->user()) {
            return response()->json([
                'code' => 401,
                'msg'  => '请先登录',
                'data' => null
            ], 401);
        }

        $tasks = HomeworkTask::orderBy('created_at', 'desc')->paginate(10);

        // 简化分页数据
        $simplifiedData = [
            'current_page' => $tasks->currentPage(),
            'data' => $tasks->items(),
            'per_page' => $tasks->perPage(),
            'total' => $tasks->total(),
            'last_page' => $tasks->lastPage(),
        ];

        return response()->json([
            'code' => 200,
            'msg'  => '获取成功',
            'data' => $simplifiedData
        ]);
    }
}