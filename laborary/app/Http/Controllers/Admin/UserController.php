<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabUser;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $excelService;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }

    public function index(Request $request)
    {
        $query = LabUser::query();

        if ($keyword = $request->input('keyword')) {
            $query->where(function($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('account', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($isActive = $request->input('is_active')) {
            $query->where('is_active', $isActive);
        }

        $query->orderBy('created_at', 'desc');

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->input('per_page', 15))
        ]);
    }

    public function destroy($id)
    {
        $user = LabUser::findOrFail($id);

        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => '不能删除自己的账号'
            ], 422);
        }

        if ($user->applicationForm()->count() > 0) {
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

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        $file = $request->file('file');

        try {
            $data = $this->excelService->import($file);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Excel文件为空或格式不正确'
                ], 422);
            }

            $successCount = 0;
            $failCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    $validated = validator($row, [
                        'account' => 'required|string|unique:lab_users,account',
                        'username' => 'required|string',
                        'email' => 'required|email|unique:lab_users,email',
                        'phone' => 'nullable|string',
                        'password' => 'nullable|min:6'
                    ])->validate();

                    LabUser::create([
                        'account' => $validated['account'],
                        'username' => $validated['username'],
                        'email' => $validated['email'],
                        'phone' => $validated['phone'] ?? null,
                        'password_hash' => Hash::make($validated['password'] ?? substr($validated['account'], -6)),
                        'role' => 1,
                        'is_active' => 1
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
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

    public function template()
    {
        $data = [
            [
                'account' => '2021001',
                'username' => '张三',
                'email' => 'zhangsan@example.com',
                'phone' => '13800138000',
                'password' => '123456'
            ],
            [
                'account' => '2021002',
                'username' => '李四',
                'email' => 'lisi@example.com',
                'phone' => '13900139000',
                'password' => ''
            ]
        ];

        $filename = $this->excelService->export($data, '学生导入模板_' . date('Ymd'));

        return response()->download(storage_path('app/' . $filename))
            ->deleteFileAfterSend(true);
    }
}
