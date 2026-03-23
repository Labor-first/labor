<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LabConfig;
use App\Models\LabNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LxController extends Controller
{
    /**
     * 获取实验室配置
     */
    public function getLabConfig()
    {
        $config = LabConfig::first();

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
     * 删除实验室配置
     */
    public function deleteLabConfig()
    {
        $config = LabConfig::first();

        if (!$config) {
            return response()->json([
                'code' => 404,
                'msg' => '实验室配置不存在',
                'data' => null
            ], 404);
        }

        $config->delete();

        return response()->json([
            'code' => 200,
            'msg' => '删除成功',
            'data' => null
        ]);
    }

    /**
     * 获取部门列表
     */
    public function getDepartments(Request $request)
    {
        $query = Department::query();

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
        $department = Department::find($id);

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
     * 创建部门
     */
    public function createDepartment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'intro' => 'nullable|string',
            'tech_stack' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $department = Department::create($validator->validated());

        return response()->json([
            'code' => 200,
            'msg' => '创建成功',
            'data' => $department
        ]);
    }

    /**
     * 更新部门
     */
    public function updateDepartment(Request $request, $id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'code' => 404,
                'msg' => '部门不存在',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255|unique:departments,name,' . $id,
            'intro' => 'nullable|string',
            'tech_stack' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $department->update($validator->validated());

        return response()->json([
            'code' => 200,
            'msg' => '更新成功',
            'data' => $department
        ]);
    }

    /**
     * 删除部门
     */
    public function deleteDepartment($id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'code' => 404,
                'msg' => '部门不存在',
                'data' => null
            ], 404);
        }

        // 检查部门下是否有关联的用户或报名表配置
        if ($department->labUsers()->count() > 0) {
            return response()->json([
                'code' => 400,
                'msg' => '该部门下存在用户，无法删除',
                'data' => null
            ], 400);
        }

        if ($department->registrationConfigs()->count() > 0) {
            return response()->json([
                'code' => 400,
                'msg' => '该部门下存在报名表配置，无法删除',
                'data' => null
            ], 400);
        }

        $department->delete();

        return response()->json([
            'code' => 200,
            'msg' => '删除成功',
            'data' => null
        ]);
    }



    /**
     * 获取新闻列表（支持分页）
     */
    public function getNewsList(Request $request)
    {
        $query = LabNews::with('author');

        // 可按标题搜索
        if ($request->has('title')) {
            $query->where('title', 'like', '%' . $request->input('title') . '%');
        }

        // 可按置顶筛选
        if ($request->has('is_top')) {
            $query->where('is_top', $request->input('is_top'));
        }

        // 可按作者筛选
        if ($request->has('author_id')) {
            $query->where('author_id', $request->input('author_id'));
        }

        // 排序：置顶优先，然后按创建时间倒序
        $query->orderBy('is_top', 'desc')->orderBy('created_at', 'desc');

        // 分页处理
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $list = $query->paginate($size, ['*'], 'page', $page);

        // 格式化数据，添加 author_name
        $formattedList = collect($list->items())->map(function ($news) {
            return [
                'id' => $news->id,
                'title' => $news->title,
                'content' => $news->content,
                'cover' => $news->cover,
                'is_top' => $news->is_top,
                'author_id' => $news->author_id,
                'author_name' => $news->author ? $news->author->username : null,
                'published_at' => $news->published_at,
                'created_at' => $news->created_at,
                'updated_at' => $news->updated_at,
            ];
        });

        $data = [
            'list' => $formattedList,
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

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => $news
        ]);
    }

    /**
     * 创建新闻
     */
    public function createNews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'cover' => 'nullable|string|max:255',
            'is_top' => 'nullable|integer|in:0,1',
            'author_id' => 'required|integer|exists:lab_users,id',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();
        
        // 默认当前时间
        if (!isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $news = LabNews::create($validated);

        return response()->json([
            'code' => 200,
            'msg' => '创建成功',
            'data' => $news
        ]);
    }

    /**
     * 更新新闻
     */
    public function updateNews(Request $request, $id)
    {
        $news = LabNews::find($id);

        if (!$news) {
            return response()->json([
                'code' => 404,
                'msg' => '新闻不存在',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'cover' => 'nullable|string|max:255',
            'is_top' => 'nullable|integer|in:0,1',
            'author_id' => 'nullable|integer|exists:lab_users,id',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $news->update($validator->validated());

        return response()->json([
            'code' => 200,
            'msg' => '更新成功',
            'data' => $news
        ]);
    }

    /**
     * 删除新闻
     */
    public function deleteNews($id)
    {
        $news = LabNews::find($id);

        if (!$news) {
            return response()->json([
                'code' => 404,
                'msg' => '新闻不存在',
                'data' => null
            ], 404);
        }

        $news->delete();

        return response()->json([
            'code' => 200,
            'msg' => '删除成功',
            'data' => null
        ]);
    }
}
