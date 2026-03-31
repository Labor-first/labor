<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 引入控制器
use App\Http\Controllers\FmyController;
use App\Http\Controllers\LxController;
use App\Http\Controllers\WjcController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\TrainingWeekController;

// [测试] 检查 API 服务是否正常运行，返回当前时间
Route::get('/test', function () {
    return response()->json(['msg' => 'API 正常工作', 'time' => now()]);
});


// --- 实验室配置模块 ---
Route::prefix('lab')->group(function () {
    Route::get('/config', [LxController::class, 'getLabConfig']);//获取实验室全局配置信息（公开）
});
// --- 实验室配置管理 (管理员专用) ---
Route::middleware(['auth:api', 'admin.role'])->prefix('admin/lab')->group(function () {
    Route::post('/create', [LxController::class, 'createLab']); //建立实验室
    Route::post('/config', [LxController::class, 'saveLabConfig']);//保存/更新实验室配置
    Route::delete('/config', [LxController::class, 'deleteLabConfig']);//删除实验室配置
});

// --- 部门管理模块 ---
Route::prefix('departments')->group(function () {
    Route::get('/', [LxController::class, 'getDepartments']);//获取所有部门列表（公开）
    Route::get('/{id}', [LxController::class, 'getDepartmentDetail']);//获取指定 ID 的部门详细信息（公开）
});
// --- 部门管理 (管理员专用) ---
Route::middleware(['auth:api', 'admin.role'])->prefix('admin/departments')->group(function () {
    Route::post('/', [LxController::class, 'createDepartment']);// 新增一个部门
    Route::put('/{id}', [LxController::class, 'updateDepartment']); // 修改指定 ID 的部门信息
    Route::delete('/{id}', [LxController::class, 'deleteDepartment']);// 删除指定 ID 的部门
});

// --- 新闻公告模块 ---
Route::prefix('lab-news')->group(function () {
    Route::get('/', [LxController::class, 'getNewsList']);//获取所有新闻列表（公开）
    Route::get('/{id}', [LxController::class, 'getNewsDetail']);//获取指定 ID 的新闻详细内容（公开）
});
// --- 新闻公告管理 (管理员专用) ---
Route::middleware(['auth:api', 'admin.role'])->prefix('admin/lab-news')->group(function () {
    Route::post('/', [LxController::class, 'createNews']);//发布新新闻
    Route::put('/{id}', [LxController::class, 'updateNews']);//编辑指定 ID 的新闻
    Route::delete('/{id}', [LxController::class, 'deleteNews']);//删除指定 ID 的新闻
});




// --- 用户认证模块 (无需登录) ---
// 专门用于用户登录和激活流程
Route::prefix('user')->group(function () {
    Route::post('/login', [WjcController::class, 'login'])->name('login');//用户登录（账号密码验证，返回 Token）
    Route::post('/send-activation-code', [WjcController::class, 'sendActivationCode']);//发送账户激活码（通常发送到邮箱或手机）
    Route::post('/verify-activation-code', [WjcController::class, 'verifyActivationCode']);//验证用户提交的激活码是否正确
});

// --- 忘记密码模块 (无需登录) ---
Route::prefix('forgot-password')->group(function () {
    Route::post('/send-code', [FmyController::class, 'sendResetCode']);//发送重置密码验证码到邮箱
    Route::post('/reset', [FmyController::class, 'resetPassword']);//验证验证码并重置密码
});



// --- 用户个人中心 (需登录) ---
Route::middleware('auth:api')->group(function () {
    Route::prefix('user')->group(function () {
        Route::post('/logout', [WjcController::class, 'logout']);//用户登出（使当前 Token 失效）
        Route::post('/update-info', [WjcController::class, 'updateInfo']);//修改用户个人资料（如姓名、电话等）
        Route::get('/me', [FmyController::class, 'me']);//获取当前登录用户的详细信息
        Route::post('/change-password', [FmyController::class, 'changePassword']);//修改登录密码
    });
});

// [提交] 提交新的报名表单
Route::post('/registration', [FmyController::class, 'registrationStore']);//提交新的报名表单
Route::get('/registration/status', [FmyController::class, 'CheckRegistrationStatus']);//查看当前用户的报名状态（需传参 config_id）
Route::post('/registration/cancel', [FmyController::class, 'cancelRegistration']);//取消/撤回已提交的报名申请

Route::middleware(['auth:api', 'admin.role'])->prefix('admin')->group(function () {
    Route::post('/training-notifications', [FmyController::class, 'store']);//发布/保存通知
    Route::get('/training-notifications/drafts', [FmyController::class, 'getDrafts']);//获取当前登录用户的草稿列表
});

// 培训管理接口
Route::get('/training/stats', [WjcController::class, 'stats']);
Route::get('/training/learn-progress', [WjcController::class, 'learnProgress']);
Route::get('/training/homework/pending-correction', [WjcController::class, 'pendingCorrection']);
Route::get('/training/homework/{homeworkId}', [WjcController::class, 'homeworkDetail']);
Route::put('/training/homework/{homeworkId}/correct', [WjcController::class, 'correctHomework']);

// 查看个人作业批改情况
Route::get('/trainee/task/correct/{taskId}', [WjcController::class, 'getTaskCorrectInfo']);// 查看个人作业批改情况
Route::get('/trainee/task/echo/{taskId}', [WjcController::class, 'echoTask']);// 作业回显接口
Route::post('/admin/task/publish', [WjcController::class, 'publishTask']);// 发布作业



// --- 表单草稿模块 (公开) ---
// 支持断点续填功能，保存未完成的表单数据（无需登录，使用device_id标识）
Route::prefix('drafts')->group(function () {
    Route::post('/', [LxController::class, 'saveDraft']);//保存/更新表单草稿
    Route::get('/', [LxController::class, 'getDraft']);//获取指定类型的草稿
    Route::get('/load', [LxController::class, 'loadDraft']);//草稿回显接口 - 页面加载时获取草稿数据
    Route::get('/list', [LxController::class, 'getDraftList']);//获取设备的所有草稿列表
    Route::delete('/{id}', [LxController::class, 'deleteDraft']);//删除指定草稿
    Route::delete('/', [LxController::class, 'clearAllDrafts']);//清空所有草稿
});


// --- 报名管理 (管理员专用) ---
Route::middleware(['auth:api', 'admin.role'])->prefix('admin')->group(function () {
    Route::get('/applications', [ApplicationController::class, 'index']);//获取所有报名记录列表（支持筛选/搜索）
    Route::put('/applications/{id}/audit', [ApplicationController::class, 'audit']);//审核指定的报名申请（通过/拒绝）
    Route::get('/applications/export', [ApplicationController::class, 'export']);//导出报名数据为 Excel/CSV 文件



    // --- 用户管理 (管理员专用) ---
    Route::get('/users', [UserController::class, 'index']);//获取系统所有用户列表
    Route::delete('/users/{id}', [UserController::class, 'destroy']);//强制删除指定 ID 的用户账号
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);//强制重置指定用户的密码
    Route::post('/users/import', [UserController::class, 'import']);//批量导入用户数据（从 Excel 等文件）
    Route::get('/users/template', [UserController::class, 'template']);//下载用户导入模板



    // --- 系统设置 (管理员专用) ---
    Route::put('/system/application-toggle', [SystemController::class, 'toggleApplication']);// [开关] 控制报名通道的开启或关闭
    Route::get('/system/status', [SystemController::class, 'status']);//获取系统当前运行状态（健康检查）



    // --- 培训周次管理 (管理员专用) ---
    Route::get('/training-weeks', [TrainingWeekController::class, 'index']);
    Route::post('/training-weeks', [TrainingWeekController::class, 'store']);
    Route::get('/training-weeks/{id}', [TrainingWeekController::class, 'show']);
    Route::put('/training-weeks/{id}', [TrainingWeekController::class, 'update']);
    Route::post('/training-weeks/{id}/publish', [TrainingWeekController::class, 'publish']);
});

// --- 文件上传模块 (公开) ---
// 报名表附件上传（支持简历/作品集/证明材料）
Route::prefix('file')->group(function () {
    Route::post('/upload', [LxController::class, 'uploadFile']); // 上传文件
    Route::delete('/delete', [LxController::class, 'deleteFile']);//删除已上传的文件
});

// --- 学员问题管理 (需要登录) ---
Route::middleware('auth:api')->prefix('questions')->group(function () {
    Route::post('/', [LxController::class, 'createQuestion']);//新增问题
    Route::get('/', [LxController::class, 'getQuestions']);//获取问题列表
    Route::get('/{id}', [LxController::class, 'getQuestionDetail']);//获取单个问题详情
    Route::put('/{id}', [LxController::class, 'updateQuestion']);//修改问题
    Route::delete('/{id}', [LxController::class, 'deleteQuestion']);//删除问题
});
Route::middleware(['auth:api', 'admin.role'])->prefix('admin')->group(function () {
    Route::apiResource('faqs', \App\Http\Controllers\Admin\FaqController::class);
    Route::post('faqs/answer-all', [\App\Http\Controllers\Admin\FaqController::class, 'answerAll']);
    Route::post('faqs/batch-delete', [\App\Http\Controllers\Admin\FaqController::class, 'batchDelete']);
});
