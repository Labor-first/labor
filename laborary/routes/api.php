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
    // [读取] 获取实验室的全局配置信息（公开）
    Route::get('/config', [LxController::class, 'getLabConfig']);
});

// --- 实验室配置管理 (管理员专用) ---
Route::middleware(['auth:api', 'admin.role'])->prefix('admin/lab')->group(function () {
    //建立实验室
    Route::post('/create', [LxController::class, 'createLab']);
    //保存/更新实验室配置
    Route::post('/config', [LxController::class, 'saveLabConfig']);
    //删除实验室配置
    Route::delete('/config', [LxController::class, 'deleteLabConfig']);
});

// --- 部门管理模块 ---
Route::prefix('departments')->group(function () {
    //获取所有部门列表（公开）
    Route::get('/', [LxController::class, 'getDepartments']);
    //获取指定 ID 的部门详细信息（公开）
    Route::get('/{id}', [LxController::class, 'getDepartmentDetail']);
});

// --- 部门管理 (管理员专用) ---
Route::middleware(['auth:api', 'admin.role'])->prefix('admin/departments')->group(function () {
    // 新增一个部门
    Route::post('/', [LxController::class, 'createDepartment']);
    // 修改指定 ID 的部门信息
    Route::put('/{id}', [LxController::class, 'updateDepartment']);
    // 删除指定 ID 的部门
    Route::delete('/{id}', [LxController::class, 'deleteDepartment']);
});

// --- 新闻公告模块 ---
Route::prefix('lab-news')->group(function () {
    //获取新闻列表（支持分页/筛选）（公开）
    Route::get('/', [LxController::class, 'getNewsList']);
    //获取指定 ID 的新闻详细内容（公开）
    Route::get('/{id}', [LxController::class, 'getNewsDetail']);
});

// --- 新闻公告管理 (管理员专用) ---
Route::middleware(['auth:api', 'admin.role'])->prefix('admin/lab-news')->group(function () {
    //发布新新闻
    Route::post('/', [LxController::class, 'createNews']);
    //编辑指定 ID 的新闻
    Route::put('/{id}', [LxController::class, 'updateNews']);
    //删除指定 ID 的新闻
    Route::delete('/{id}', [LxController::class, 'deleteNews']);
});

// --- 用户认证模块 (无需登录) ---
// 专门用于用户登录和激活流程
Route::prefix('user')->group(function () {
    //用户登录（账号密码验证，返回 Token）
    Route::post('/login', [WjcController::class, 'login'])->name('login');

    //发送账户激活码（通常发送到邮箱或手机）
    Route::post('/send-activation-code', [WjcController::class, 'sendActivationCode']);

    //验证用户提交的激活码是否正确
    Route::post('/verify-activation-code', [WjcController::class, 'verifyActivationCode']);
});

// --- 忘记密码模块 (无需登录) ---
Route::prefix('forgot-password')->group(function () {
    //发送重置密码验证码到邮箱
    Route::post('/send-code', [FmyController::class, 'sendResetCode']);

    //验证验证码并重置密码
    Route::post('/reset', [FmyController::class, 'resetPassword']);
});




Route::middleware('auth:api')->group(function () {

    // --- 用户个人中心 (需登录) ---
    Route::prefix('user')->group(function () {
        // [认证] 用户登出（使当前 Token 失效）
        Route::post('/logout', [WjcController::class, 'logout']);

        // [更新] 修改用户个人资料（如姓名、电话等）
        Route::post('/update-info', [WjcController::class, 'updateInfo']);

        // [读取] 获取当前登录用户的详细信息
        Route::get('/me', [FmyController::class, 'me']);

        // [安全] 修改登录密码
        Route::post('/change-password', [FmyController::class, 'changePassword']);

        //发布/保存通知
        Route::post('/training-notifications', [FmyController::class, 'store']);
        //获取当前登录用户的草稿列表
        Route::get('/training-notifications/drafts', [FmyController::class, 'getDrafts']);
    });
});

// [提交] 提交新的报名表单
Route::post('/registration', [FmyController::class, 'registrationStore']);

// [查询] 查看当前用户的报名状态（需传参 config_id）
Route::get('/registration/status', [FmyController::class, 'CheckRegistrationStatus']);

// [撤销] 取消/撤回已提交的报名申请
Route::post('/registration/cancel', [FmyController::class, 'cancelRegistration']);



// 培训管理接口
Route::get('/training/stats', [WjcController::class, 'stats']);
Route::get('/training/learn-progress', [WjcController::class, 'learnProgress']);
Route::get('/training/homework/pending-correction', [WjcController::class, 'pendingCorrection']);
Route::get('/training/homework/{homeworkId}', [WjcController::class, 'homeworkDetail']);
Route::put('/training/homework/{homeworkId}/correct', [WjcController::class, 'correctHomework']);

// --- 表单草稿模块 (公开) ---
// 支持断点续填功能，保存未完成的表单数据（无需登录，使用device_id标识）
Route::prefix('drafts')->group(function () {
    // [写入] 保存/更新表单草稿
    Route::post('/', [LxController::class, 'saveDraft']);
    // [读取] 获取指定类型的草稿
    Route::get('/', [LxController::class, 'getDraft']);
    // [读取] 草稿回显接口 - 页面加载时获取草稿数据
    Route::get('/load', [LxController::class, 'loadDraft']);
    // [读取] 获取设备的所有草稿列表
    Route::get('/list', [LxController::class, 'getDraftList']);
    // [删除] 删除指定草稿
    Route::delete('/{id}', [LxController::class, 'deleteDraft']);
    // [删除] 清空所有草稿
    Route::delete('/', [LxController::class, 'clearAllDrafts']);
});

Route::middleware(['auth:api', 'admin.role'])->prefix('admin')->group(function () {
    // --- 报名管理 (管理员专用) ---
    // [读取] 获取所有报名记录列表（支持筛选/搜索）
    Route::get('/applications', [ApplicationController::class, 'index']);

    // [审核] 审核指定的报名申请（通过/拒绝）
    Route::put('/applications/{id}/audit', [ApplicationController::class, 'audit']);

    // [导出] 导出报名数据为 Excel/CSV 文件
    Route::get('/applications/export', [ApplicationController::class, 'export']);

    // --- 用户管理 (管理员专用) ---
    // [读取] 获取系统所有用户列表
    Route::get('/users', [UserController::class, 'index']);

    // [删除] 强制删除指定 ID 的用户账号
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // [重置] 强制重置指定用户的密码
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

    // [导入] 批量导入用户数据（从 Excel 等文件）
    Route::post('/users/import', [UserController::class, 'import']);

    // [模板] 下载用户导入模板
    Route::get('/users/template', [UserController::class, 'template']);

    // --- 系统设置 (管理员专用) ---
    // [开关] 控制报名通道的开启或关闭
    Route::put('/system/application-toggle', [SystemController::class, 'toggleApplication']);

    // [监控] 获取系统当前运行状态（健康检查）
    Route::get('/system/status', [SystemController::class, 'status']);

    // --- 培训周次管理 (管理员专用) ---
    Route::get('/training-weeks', [TrainingWeekController::class, 'index']);
    Route::post('/training-weeks', [TrainingWeekController::class, 'store']);
    Route::get('/training-weeks/{id}', [TrainingWeekController::class, 'show']);
    Route::put('/training-weeks/{id}', [TrainingWeekController::class, 'update']);
    Route::post('/training-weeks/{id}/publish', [TrainingWeekController::class, 'publish']);
});