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
        $query = LabUser::with(['department' => function($q) {
            $q->select('id', 'name');
        }]);

        if ($keyword = $request->input('keyword')) {
            $query->where(function($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('account', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($isActive = $request->input('is_active')) {
            $query->where('is_active', $isActive);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        $paginator = $query->paginate($perPage);

     
        $users = $paginator->getCollection();

        $formattedData = $users->transform(function($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'phone' => $user->phone,
                'email' => $user->email,
                'account' => $user->account,
                'department' => $user->department?->name ?? null,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ];
        });

      
        return response()->json([
            'success' => true,
            'data' => $formattedData->values(), // values() 重置数组索引为 0, 1, 2...
            
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
                        'account' => 'required|string',
                        'username' => 'required|string',
                        'email' => 'required|email',
                        'phone' => 'nullable',
                        'password' => 'nullable|min:6'
                    ])->validate();

                    $user = LabUser::where('account', $validated['account'])
                        ->first();
                    
                    if (!$user) {
                        $user = LabUser::where('email', $validated['email'])->first();
                    }

                    if ($user) {
                        $user->update([
                            'username' => $validated['username'],
                            'email' => $validated['email'],
                            'phone' => (string)($validated['phone'] ?? ''),
                            'password_hash' => Hash::make($validated['password'] ?? substr($validated['account'], -6)),
                        ]);
                    } else {
                        LabUser::create([
                            'account' => $validated['account'],
                            'username' => $validated['username'],
                            'email' => $validated['email'],
                            'phone' => (string)($validated['phone'] ?? ''),
                            'password_hash' => Hash::make($validated['password'] ?? substr($validated['account'], -6)),
                            'role' => 1,
                            'is_active' => 1
                        ]);
                    }

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
