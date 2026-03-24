<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabUser;
use App\Models\ApplicationForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemController extends Controller
{
    /**
     * 设置报名开关
     * PUT /api/admin/system/application-toggle
     */
    public function toggleApplication(Request $request)
    {
        $request->validate([
            'is_open' => 'required|boolean'
        ]);

        // 将开关状态存入缓存（方便全局快速读取）
        Cache::put('application_is_open', $request->is_open, 365 * 24 * 60);

        // 同时可存入数据库配置表（如果需要持久化）
        // Setting::updateOrCreate(['key' => 'application_is_open'], ['value' => $request->is_open]);

        return response()->json([
            'success' => true,
            'message' => $request->is_open ? '报名已开启' : '报名已关闭',
            'data' => [
                'is_open' => $request->is_open
            ]
        ]);
    }

    /**
     * 获取系统状态
     * GET /api/admin/system/status
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'application_is_open' => Cache::get('application_is_open', true),
                'total_users' => LabUser::count(),
                'total_applications' => ApplicationForm::count(),
                'pending_applications' => ApplicationForm::where('status', ApplicationForm::STATUS_PENDING)->count()
            ]
        ]);
    }
}