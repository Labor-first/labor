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

/* ==========================================================================
   1. 公开接口 (不需要登录 / Public Routes)
   任何人都可以访问，通常用于登录前获取数据或执行登录操作
   ========================================================================== */

// [测试] 检查 API 服务是否正常运行，返回当前时间
Route::get('/test', function () {
    return response()->json(['msg' => 'API 正常工作', 'time' => now()]);
});

// --- 实验室配置模块 (公开) ---
Route::prefix('lab')->group(function () {
    // [读取] 获取实验室的全局配置信息
    Route::get('/config', [LxController::class, 'getLabConfig']);

    // [写入] 保存/更新实验室配置
    Route::post('/config', [LxController::class, 'saveLabConfig']);

    // [删除] 删除实验室配置
    Route::delete('/config', [LxController::class, 'deleteLabConfig']);
});

// --- 部门管理模块 (公开) ---
Route::prefix('departments')->group(function () {
    // 获取所有部门列表
    Route::get('/', [LxController::class, 'getDepartments']);
    // 获取指定 ID 的部门详细信息
    Route::get('/{id}', [LxController::class, 'getDepartmentDetail']);

    //新增一个部门
    Route::post('/', [LxController::class, 'createDepartment']);
    // 修改指定 ID 的部门信息
    Route::put('/{id}', [LxController::class, 'updateDepartment']);
    // 删除指定 ID 的部门
    Route::delete('/{id}', [LxController::class, 'deleteDepartment']);
});

// --- 新闻公告模块 (公开) ---
Route::prefix('lab-news')->group(function () {
    // [读取] 获取新闻列表（支持分页/筛选）
    Route::get('/', [LxController::class, 'getNewsList']);
    // [读取] 获取指定 ID 的新闻详细内容
    Route::get('/{id}', [LxController::class, 'getNewsDetail']);

    // 发布新新闻
    Route::post('/', [LxController::class, 'createNews']);
    // 编辑指定 ID 的新闻
    Route::put('/{id}', [LxController::class, 'updateNews']);
    // 删除指定 ID 的新闻
    Route::delete('/{id}', [LxController::class, 'deleteNews']);
});

// --- 用户认证模块 (无需登录) ---
// 专门用于用户登录和激活流程
Route::prefix('user')->group(function () {
    // [认证] 用户登录（账号密码验证，返回 Token）
    Route::post('/login', [WjcController::class, 'login'])->name('login');

    // [通知] 发送账户激活码（通常发送到邮箱或手机）
    Route::post('/send-activation-code', [WjcController::class, 'sendActivationCode']);

    // [验证] 验证用户提交的激活码是否正确
    Route::post('/verify-activation-code', [WjcController::class, 'verifyActivationCode']);
});

// --- 兼容旧版路径 (公开) ---
// 为了兼容前端旧代码，保留不带前缀的直接访问路径
Route::get('/departments', [LxController::class, 'getDepartments']);             // [读取] 部门列表
Route::get('/departments/{id}', [LxController::class, 'getDepartmentDetail']);   // [读取] 部门详情
Route::get('/lab-news', [LxController::class, 'getNewsList']);                   // [读取] 新闻列表
Route::get('/lab-news/{id}', [LxController::class, 'getNewsDetail']);            // [读取] 新闻详情


/* ==========================================================================
   2. 需要登录的接口 (Protected Routes)
   必须携带有效的 JWT Token (auth:api) 才能访问
   ========================================================================== */
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
    });
});

// [提交] 提交新的报名表单
Route::post('/registration', [FmyController::class, 'registrationStore']);

// [查询] 查看当前用户的报名状态（需传参 config_id）
Route::get('/registration/status', [FmyController::class, 'getRegistrationStatus']);

// [撤销] 取消/撤回已提交的报名申请
Route::post('/registration/cancel', [FmyController::class, 'cancelRegistration']);



/* ==========================================================================
   3. 管理后台路由组 (Admin Routes)
   必须同时满足：1. 已登录 (auth:sanctum)  2. 具有管理员角色 (admin.role)
   ========================================================================== */
Route::middleware(['auth:api'])->prefix('admin')->group(function () {
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

    // --- 系统设置 (管理员专用) ---
    // [开关] 控制报名通道的开启或关闭
    Route::put('/system/application-toggle', [SystemController::class, 'toggleApplication']);

    // [监控] 获取系统当前运行状态（健康检查）
    Route::get('/system/status', [SystemController::class, 'status']);
});
