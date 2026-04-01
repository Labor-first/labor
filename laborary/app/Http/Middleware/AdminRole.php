<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        // 检查用户是否登录
        
        if (!$request->user()) {
            return response()->json([
                'code' => 401,
                'msg' => '未登录或登录已过期，请先登录',
                'data' => null
            ]);
        }

        // 检查角色是否为管理员 (role = 2)
        if ($request->user()->role !== 2) {
            return response()->json([
                'code' => 403,
                'msg' => '没有权限执行此操作',
                'data' => null
            ]);
        }

        return $next($request);
    }
}