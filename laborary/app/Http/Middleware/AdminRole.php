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
                'success' => false,
                'message' => '请先登录'
            ], 401);
        }

        // 检查角色是否为管理员 (role = 2)
        if ($request->user()->role !== 2) {
            return response()->json([
                'success' => false,
                'message' => '无权访问管理后台'
            ], 403);
        }

        return $next($request);
    }
}