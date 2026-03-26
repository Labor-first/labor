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

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // 排序：最新在前
        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $applications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    public function export(Request $request)
    {
        $status = $request->input('status', ApplicationForm::STATUS_APPROVED); // 默认导出通过的

        $applications = ApplicationForm::with('user')
            ->where('status', $status)
            ->get();

        if ($applications->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '没有找到符合条件的报名数据'
            ], 404);
        }

        $data = $applications->map(function($app) {
            return [
                '账号' => $app->user?->account ?? '',
                '用户名' => $app->user?->username ?? '',
                '手机号' => $app->user?->phone ?? '',
                '邮箱' => $app->user?->email ?? '',
                '姓名' => $app->name,
                '班级' => $app->class,
                '学院' => $app->academy,
                '专业' => $app->major,
                '状态' => $this->getStatusText($app->status),
                '审核意见' => $app->audit_remark ?? '',
                '报名时间' => $app->created_at->format('Y-m-d H:i:s')
            ];
        })->toArray();

        $filename = $this->excelService->export($data, '报名数据_' . date('Ymd'));

        return response()->download(storage_path('app/' . $filename))
            ->deleteFileAfterSend(true);
    }

    private function getStatusText($status)
    {
        $map = [
            ApplicationForm::STATUS_PENDING => '待审核',
            ApplicationForm::STATUS_APPROVED => '已通过',
            ApplicationForm::STATUS_CANCELLED => '已取消',
            ApplicationForm::STATUS_REJECTED => '已拒绝'
        ];
        return $map[$status] ?? '未知';
    }

    public function audit(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in([
                ApplicationForm::STATUS_PENDING,
                ApplicationForm::STATUS_APPROVED,
                ApplicationForm::STATUS_CANCELLED,
                ApplicationForm::STATUS_REJECTED
            ])],
            'audit_remark' => 'nullable|string|max:500'
        ]);

        $application = ApplicationForm::findOrFail($id);

        // 状态变更验证
        if ($application->status === ApplicationForm::STATUS_APPROVED && $request->status === ApplicationForm::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => '已录取的报名不能退回待审核状态'
            ], 422);
        }

        if ($application->status === ApplicationForm::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => '已取消的报名无法审核'
            ], 422);
        }

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
            'message' => $this->getAuditMessage($request->status),
            'data' => [
                'id' => $application->id,
                'user_id' => $application->user_id,
                'name' => $application->name,
                'class' => $application->class,
                'academy' => $application->academy,
                'major' => $application->major,
                'sign_reason' => $application->sign_reason,
                'status' => $application->status,
                'status_text' => $this->getStatusText($application->status),
                'audit_remark' => $application->audit_remark,
                'audit_time' => $application->audit_time?->format('Y-m-d H:i:s'),
                'created_at' => $application->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $application->updated_at->format('Y-m-d H:i:s'),
                'user' => $application->user ? [
                    'id' => $application->user->id,
                    'account' => $application->user->account,
                    'username' => $application->user->username,
                    'email' => $application->user->email,
                    'phone' => $application->user->phone,
                ] : null,
            ]
        ]);
    }

    private function getAuditMessage($status)
    {
        $messages = [
            ApplicationForm::STATUS_PENDING => '已退回待审核',
            ApplicationForm::STATUS_APPROVED => '审核通过，已录取',
            ApplicationForm::STATUS_CANCELLED => '已取消报名',
            ApplicationForm::STATUS_REJECTED => '审核拒绝',
        ];
        return $messages[$status] ?? '审核完成';
    }

    /**
     * 获取报名审核流程状态（动态反映审核进度）
     * GET /api/admin/applications/{id}/audit-flow
     */
    public function auditFlow($id)
    {
        $application = ApplicationForm::with(['user' => function($q) {
            $q->select('id', 'account', 'username', 'email', 'phone');
        }])->findOrFail($id);

        // 定义审核流程步骤
        $flowSteps = [
            [
                'step' => 1,
                'title' => '提交报名',
                'status' => 'completed',
                'description' => '用户已成功提交报名信息',
                'time' => $application->created_at->format('Y-m-d H:i:s'),
                'icon' => 'submit'
            ],
            [
                'step' => 2,
                'title' => '待审核',
                'status' => $application->status === ApplicationForm::STATUS_PENDING ? 'current' : 
                           ($application->status > ApplicationForm::STATUS_PENDING ? 'completed' : 'pending'),
                'description' => '等待管理员审核',
                'time' => $application->created_at->format('Y-m-d H:i:s'),
                'icon' => 'pending'
            ],
            [
                'step' => 3,
                'title' => '审核中',
                'status' => $application->audit_time ? 'completed' : 
                           ($application->status === ApplicationForm::STATUS_PENDING ? 'pending' : 'current'),
                'description' => '管理员正在审核',
                'time' => $application->audit_time ? $application->audit_time->format('Y-m-d H:i:s') : null,
                'icon' => 'processing'
            ],
            [
                'step' => 4,
                'title' => '审核结果',
                'status' => in_array($application->status, [
                    ApplicationForm::STATUS_APPROVED, 
                    ApplicationForm::STATUS_REJECTED
                ]) ? 'completed' : 'pending',
                'description' => $this->getStatusText($application->status),
                'time' => $application->audit_time ? $application->audit_time->format('Y-m-d H:i:s') : null,
                'icon' => $application->status === ApplicationForm::STATUS_APPROVED ? 'success' : 
                         ($application->status === ApplicationForm::STATUS_REJECTED ? 'reject' : 'pending'),
                'result' => [
                    'status' => $application->status,
                    'status_text' => $this->getStatusText($application->status),
                    'remark' => $application->audit_remark
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'application' => [
                    'id' => $application->id,
                    'name' => $application->name,
                    'class' => $application->class,
                    'academy' => $application->academy,
                    'major' => $application->major,
                    'status' => $application->status,
                    'status_text' => $this->getStatusText($application->status),
                    'created_at' => $application->created_at->format('Y-m-d H:i:s'),
                ],
                'user' => $application->user ? [
                    'id' => $application->user->id,
                    'account' => $application->user->account,
                    'username' => $application->user->username,
                    'email' => $application->user->email,
                    'phone' => $application->user->phone,
                ] : null,
                'flow' => $flowSteps,
                'current_step' => collect($flowSteps)->firstWhere('status', 'current')['step'] ?? 
                                 (collect($flowSteps)->where('status', 'completed')->last()['step'] ?? 1)
            ]
        ]);
    }

    /**
     * 批量审核报名
     * POST /api/admin/applications/batch-audit
     */
    public function batchAudit(Request $request)
    {
        $request->validate([
            'ids' => 'required',
            'status' => ['required', Rule::in([
                ApplicationForm::STATUS_APPROVED,
                ApplicationForm::STATUS_REJECTED
            ])],
            'audit_remark' => 'nullable|string|max:500'
        ]);

        // 兼容字符串和数组格式
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = [$ids];
        }
        
        // 验证每个ID都是整数
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID必须是数字'
                ], 422);
            }
        }
        $status = $request->input('status');
        $remark = $request->input('audit_remark');

        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($ids as $id) {
            try {
                $application = ApplicationForm::find($id);
                
                if (!$application) {
                    $results['failed'][] = [
                        'id' => $id,
                        'reason' => '报名记录不存在'
                    ];
                    continue;
                }

                if ($application->status === ApplicationForm::STATUS_CANCELLED) {
                    $results['failed'][] = [
                        'id' => $id,
                        'name' => $application->name,
                        'reason' => '已取消的报名无法审核'
                    ];
                    continue;
                }

                $application->update([
                    'status' => $status,
                    'audit_remark' => $remark,
                    'audit_time' => now()
                ]);

                $results['success'][] = [
                    'id' => $id,
                    'name' => $application->name,
                    'status' => $status,
                    'status_text' => $this->getStatusText($status)
                ];

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'id' => $id,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "批量审核完成：成功 " . count($results['success']) . " 条，失败 " . count($results['failed']) . " 条",
            'data' => $results
        ]);
    }

    /**
     * 获取审核统计
     * GET /api/admin/applications/audit-statistics
     */
    public function auditStatistics(Request $request)
    {
        $configId = $request->input('config_id');
        
        $query = ApplicationForm::query();
        if ($configId) {
            $query->where('config_id', $configId);
        }

        $statistics = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', ApplicationForm::STATUS_PENDING)->count(),
            'approved' => (clone $query)->where('status', ApplicationForm::STATUS_APPROVED)->count(),
            'rejected' => (clone $query)->where('status', ApplicationForm::STATUS_REJECTED)->count(),
            'cancelled' => (clone $query)->where('status', ApplicationForm::STATUS_CANCELLED)->count(),
        ];

        // 今日审核统计
        $todayQuery = (clone $query)->whereDate('audit_time', today());
        $statistics['today'] = [
            'total' => $todayQuery->count(),
            'approved' => (clone $todayQuery)->where('status', ApplicationForm::STATUS_APPROVED)->count(),
            'rejected' => (clone $todayQuery)->where('status', ApplicationForm::STATUS_REJECTED)->count(),
        ];

        // 审核率
        $statistics['approval_rate'] = $statistics['total'] > 0 
            ? round($statistics['approved'] / $statistics['total'] * 100, 2) 
            : 0;

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
