<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use App\Models\LabUser;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    protected $excelService;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    /**
     * 11. 获取报名列表（分页 + 搜索 + 筛选）
     * GET /api/admin/applications
     */
    public function index(Request $request)
    {
        $query = ApplicationForm::with(['user' => function($q) {
            $q->select('id', 'account', 'username', 'email', 'phone');
        }]);

        // 搜索：账号/用户名/手机号
        if ($keyword = $request->input('keyword')) {
            $query->whereHas('user', function($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('account', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        // 筛选：状态
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // 排序：最新在前
        $query->orderBy('created_at', 'desc');

        // 分页
        $perPage = $request->input('per_page', 15);
        $applications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * 12. 审核报名
     * PUT /api/admin/applications/{id}/audit
     */
    public function audit(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in([1, 2, 3, 4])],
            'audit_remark' => 'nullable|string|max:500'
        ]);

        $application = ApplicationForm::findOrFail($id);

        // 状态变更验证
        if ($application->status === 2 && $request->status === 1) {
            return response()->json([
                'success' => false,
                'message' => '已录取的报名不能退回待审核状态'
            ], 422);
        }

        // 更新状态和审核意见
        $application->update([
            'status' => $request->status,
            'audit_remark' => $request->audit_remark,
            'audit_time' => now()
        ]);

        // 如果通过，可触发后续逻辑（如发送通知邮件）
        if ($request->status === 2) {
            // TODO: 发送录取通知邮件
            // Mail::to($application->user->email)->send(new ApprovedNotification($application));
        }

        return response()->json([
            'success' => true,
            'message' => '审核成功',
            'data' => $application
        ]);
    }

    /**
     * 13. 导出报名数据（Excel）
     * GET /api/admin/applications/export
     */
    public function export(Request $request)
    {
        $status = $request->input('status', 2); // 默认导出通过的

        $applications = ApplicationForm::with('user')
            ->where('status', $status)
            ->get();

        // 准备导出数据
        $data = $applications->map(function($app) {
            return [
                '账号' => $app->user->account,
                '用户名' => $app->user->username,
                '手机号' => $app->user->phone,
                '邮箱' => $app->user->email,
                '姓名' => $app->name,
                '班级' => $app->class,
                '学院' => $app->academy,
                '专业' => $app->major,
                '状态' => $this->getStatusText($app->status),
                '审核意见' => $app->audit_remark,
                '报名时间' => $app->created_at->format('Y-m-d H:i:s')
            ];
        })->toArray();

        // 调用 Excel 服务生成文件
        $filename = $this->excelService->export($data, '报名数据_' . date('Ymd'));

        return response()->download(storage_path('app/' . $filename))
            ->deleteFileAfterSend(true);
    }

    /**
     * 状态文本转换
     */
    private function getStatusText($status)
    {
        $map = [
            1 => '待审核',
            2 => '已通过',
            3 => '已取消',
            4 => '已拒绝'
        ];
        return $map[$status] ?? $status;
    }
}