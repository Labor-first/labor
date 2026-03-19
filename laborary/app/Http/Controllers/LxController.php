<?php

namespace app\Http\Controllers;

use App\Models\Department;
use App\Models\LabConfig;
use App\Models\LabNews;
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



     /**
     * 获取部门详情
     */
    public function getDepartmentDetail($id)
    {
        $department = Department::with(['labUsers', 'registrationConfigs'])->find($id);

        if (!$department) {
            return response()->json([
                'code' => 404,
                'msg' => '部门不存在',
                'data' => null
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => $department
        ]);
    }



    /**
     * 获取新闻列表（支持分页）
     */
    public function getNewsList(Request $request)
    {
        $query = LabNews::query();

        // 可按标题搜索
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->input('title') . '%');
        }

        // 可按置顶筛选
        if ($request->has('is_top')) {
            $query->where('is_top', $request->input('is_top'));
        }

        // 排序：置顶优先，然后按创建时间倒序
        $query->orderBy('is_top', 'desc')->orderBy('created_at', 'desc');

        // 分页处理
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $list = $query->paginate($size, ['*'], 'page', $page);

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

    /**
     * 获取新闻详情
     */
    public function getNewsDetail($id)
    {
        $news = LabNews::with('author')->find($id);

        if (!$news) {
            return response()->json([
                'code' => 404,
                'msg' => '新闻不存在',
                'data' => null
            ], 404);
        }

        // 增加浏览量
        $news->increment('view_count');

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => $news
        ]);
    }
}
