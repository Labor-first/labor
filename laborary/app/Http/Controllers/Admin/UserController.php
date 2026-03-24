<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabUser;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected $excelService;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    /**
     * 9. 获取用户列表
     * GET /api/admin/users
     */
    public function index(Request $request)
    {
        $query = LabUser::query();

        // 搜索
        if ($keyword = $request->input('keyword')) {
            $query->where(function($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('account', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 筛选角色
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // 筛选激活状态
        if ($isActive = $request->input('is_active')) {
            $query->where('is_active', $isActive);
        }

        $query->orderBy('created_at', 'desc');

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->input('per_page', 15))
        ]);
    }

    /**
     * 10. 删除用户
     * DELETE /api/admin/users/{id}
     */
    public function destroy($id)
    {
        $user = LabUser::findOrFail($id);

        // 防止删除管理员自己
        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => '不能删除自己的账号'
            ], 422);
        }

        // 检查是否有报名记录
        if ($user->applicationForm()->exists()) {
            return response()->json([
                'success' => false,
                'message' => '该用户有报名记录，无法删除'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => '用户已删除'
        ]);
    }

    /**
     * 重置密码
     * POST /api/admin/users/{id}/reset-password
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'new_password' => 'required|min:6'
        ]);

        $user = LabUser::findOrFail($id);

        $user->update([
            'password_hash' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => '密码已重置'
        ]);
    }

    /**
     * 8. Excel 批量导入学生
     * POST /api/admin/users/import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 最大 10MB
        ]);

        $file = $request->file('file');

        try {
            // 解析 Excel 文件
            $data = $this->excelService->import($file);

            $successCount = 0;
            $failCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    // 验证数据
                    $validated = validator($row, [
                        'account' => 'required|string|unique:lab_users,account',
                        'username' => 'required|string',
                        'email' => 'required|email|unique:lab_users,email',
                        'phone' => 'nullable|string',
                        'password' => 'nullable|min:6'
                    ])->validate();

                    // 创建用户（默认密码为学号后6位或123456）
                    LabUser::create([
                        'account' => $validated['account'],
                        'username' => $validated['username'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                        'password_hash' => Hash::make($validated['password'] ?? substr($validated['account'], -6)),
                        'role' => 1, // 默认学生角色
                        'is_active' => 1
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = [
                        'row' => $index + 2, // Excel 行号（从第2行开始）
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '导入完成',
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '导入失败：' . $e->getMessage()
            ], 500);
        }
    }
}
