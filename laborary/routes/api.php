<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LxController;

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

// 测试路由（不需要认证）
Route::get('/test', function () {
    return response()->json(['msg' => 'API 正常工作']);
});
