<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LxController;
use App\Http\Controllers\WjcController;

// JWT 认证路由组
Route::middleware('auth:api')->group(function () {
    
    // 实验室配置相关
    Route::prefix('lab')->group(function () {
        // 获取实验室配置
        Route::get('/config', [LxController::class, 'getLabConfig']);
        
        // 创建或更新实验室配置
        Route::post('/config', [LxController::class, 'saveLabConfig']);
    });
    
    // 部门相关
    Route::prefix('departments')->group(function () {
        // 获取部门列表
        Route::get('/', [LxController::class, 'getDepartments']);
        // 获取部门详情
        Route::get('/{id}', [LxController::class, 'getDepartmentDetail']);
    });
    
    // 新闻相关
    Route::prefix('news')->group(function () {
        // 获取新闻列表
        Route::get('/', [LxController::class, 'getNewsList']);
        // 获取新闻详情
        Route::get('/{id}', [LxController::class, 'getNewsDetail']);
    });
    
});

// 无需登录的接口
//登录
Route::post('/user/login', [WjcController::class, 'login']);
//发送激活码
Route::post('/user/send-activation-code', [WjcController::class, 'sendActivationCode']);

// 需要登陆的接口
Route::middleware('auth:api')->group(function () {
    //登出
    Route::post('/user/logout', [WjcController::class, 'logout']);
    //更新个人信息
    Route::post('/user/update-info', [WjcController::class, 'updateInfo']);
});

// 测试路由（不需要认证）
Route::get('/test', function () {
    return response()->json(['msg' => 'API 正常工作']);
});
