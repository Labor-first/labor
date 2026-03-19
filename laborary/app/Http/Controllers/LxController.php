<?php

namespace app\Http\Controllers;

use App\Models\Department;
use App\Models\LabConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LxController extends Controller
{
    /**
     * 获取实验室配置（单条）
     */
    public function getLabConfig()
    {
        $config = LabConfig::with('departments')->first();

        if (!$config) {
            return response()->json([
                'code' => 404,
                'msg' => '实验室配置不存在',
                'data' => null
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => $config
        ]);
    }

    /**
     * 创建或更新实验室配置
     */
    public function saveLabConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'intro' => 'required|string',
            'address' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();

        // 强制只有一条记录
        $config = LabConfig::first();

        if ($config) {
            $config->update($validated);
            $msg = '更新成功';
        } else {
            $config = LabConfig::create($validated);
            $msg = '创建成功';
        }

        return response()->json([
            'code' => 200,
            'msg' => $msg,
            'data' => $config
        ]);
    }

    /**
     * 获取部门列表
     */
    public function getDepartments(Request $request)
    {
        // 固定：只有一个实验室，比如 id=1
        $labId = 1;

        // 查询：这个实验室下的所有部门
        $query = Department::where('lab_id', $labId);

        // 可按部门名称搜索
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        // 分页处理
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $list = $query->paginate($size, ['*'], 'page', $page);

        // 构造响应数据
        $data = [
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => (int)$page,
            'size' => (int)$size
        ];

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => $data
        ]);
    }
}
