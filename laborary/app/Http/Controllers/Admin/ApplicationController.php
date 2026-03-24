<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
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
        $query = Application::with(['user' => function($q) {
            $q->select('id', 'student_id', 'name', 'email', 'phone');
        }]);

        // 搜索：学号/姓名/手机号
        if ($keyword = $request->input('keyword')) {
            $query->whereHas('user', function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('student_id', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        // 筛选：状态
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // 筛选：部门
        if ($department = $request->input('department')) {
            $query->where('department', $department);
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
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'review_comment' => 'nullable|string|max:500'
        ]);

        $application = Application::findOrFail($id);

        // 状态变更验证
        if ($application->status === 'approved' && $request->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => '已录取的报名不能退回待审核状态'
            ], 422);
        }

        // 更新状态和审核意见
        $application->update([
            'status' => $request->status,
            'review_comment' => $request->review_comment,
            'reviewed_at' => now(),
            'reviewer_id' => $request->user()->id
        ]);

        // 如果通过，可触发后续逻辑（如发送通知邮件）
        if ($request->status === 'approved') {
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
        $status = $request->input('status', 'approved'); // 默认导出通过的

        $applications = Application::with('user')
            ->where('status', $status)
            ->get();

        // 准备导出数据
        $data = $applications->map(function($app) {
            return [
                '学号' => $app->user->student_id,
                '姓名' => $app->user->name,
                '手机号' => $app->user->phone,
                '邮箱' => $app->user->email,
                '部门' => $app->department,
                '状态' => $this->getStatusText($app->status),
                '审核意见' => $app->review_comment,
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
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            'cancelled' => '已取消'
        ];
        return $map[$status] ?? $status;
    }
}