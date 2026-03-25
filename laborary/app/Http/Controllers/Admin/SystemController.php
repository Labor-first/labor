<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabUser;
use App\Models\ApplicationForm;
use App\Models\RegistrationConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemController extends Controller
{
    /**
     * 报名配置设置
     * PUT /api/admin/system/application-toggle
     */
    public function toggleApplication(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'reg_start_time' => 'required|date',
            'reg_end_time' => 'required|date|after:reg_start_time',
            'department_id' => 'required|integer|exists:departments,id',
            'is_open' => 'required|boolean'
        ]);

        // 创建或更新报名配置
        $config = RegistrationConfig::updateOrCreate(
            ['id' => $request->input('id', 1)], // 默认更新ID为1的配置，或传入特定ID
            [
                'title' => $request->title,
                'reg_start_time' => $request->reg_start_time,
                'reg_end_time' => $request->reg_end_time,
                'department_id' => $request->department_id,
                'is_open' => $request->is_open
            ]
        );

        // 将开关状态存入缓存（方便全局快速读取）
        Cache::put('application_is_open', $request->is_open, 365 * 24 * 60);

        return response()->json([
            'success' => true,
            'message' => $request->is_open ? '报名已开启' : '报名已关闭',
            'data' => $config
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
                'application_is_open' => Cache::get('application_is_open', true),// 报名开关状态
                'total_applications' => ApplicationForm::count(),// 总报名数
                'pending_applications' => ApplicationForm::where('status', ApplicationForm::STATUS_PENDING)->count(),// 待审核报名数
                'approved_applications' => ApplicationForm::where('status', ApplicationForm::STATUS_APPROVED, true)->count()// 已审核报名数
            ]
        ]);
    }
}