<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FormDraft;
use App\Models\LabConfig;
use App\Models\LabNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * 创建或更新实验室配置（管理员专用）
     */
    public function saveLabConfig(Request $request)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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
     * 删除实验室配置（管理员专用）
     */
    public function deleteLabConfig(Request $request)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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
     * 创建部门（管理员专用）
     */
    public function createDepartment(Request $request)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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
            'data' => [
                '部门名称' => $department->name,
                '简介' => $department->intro,
                '技术栈' => $department->tech_stack,
                '创建时间' => $department->created_at,
                '更新时间' => $department->updated_at,
            ]
        ]);
    }

    /**
     * 更新部门（管理员专用）
     */
    public function updateDepartment(Request $request, $id)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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

        $validatedData = $validator->validated();

        // 检查是否有传入任何更新内容
        if (empty($validatedData)) {
            return response()->json([
                'code' => 400,
                'msg' => '未传入任何更新内容',
                'data' => null
            ], 400);
        }

        $department->update($validatedData);

        return response()->json([
            'code' => 200,
            'msg' => '更新成功',
            'data' => [
                '部门名称' => $department->name,
                '简介' => $department->intro,
                '技术栈' => $department->tech_stack,
                '创建时间' => $department->created_at,
                '更新时间' => $department->updated_at,
            ]
        ]);
    }

    /**
     * 删除部门（管理员专用）
     */
    public function deleteDepartment(Request $request, $id)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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
        // 验证查询参数
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'is_top' => 'nullable|integer|in:0,1',
            'author_id' => 'nullable|integer|exists:lab_users,id',
            'page' => 'nullable|integer|min:1',
            'size' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '搜索字段错误：' . $validator->errors()->first(),
                'data' => $validator->errors()
            ], 400);
        }

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
     * 创建新闻（管理员专用）
     */
    public function createNews(Request $request)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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
     * 更新新闻（管理员专用）
     */
    public function updateNews(Request $request, $id)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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

        $validatedData = $validator->validated();

        // 检查是否有传入任何更新内容
        if (empty($validatedData)) {
            return response()->json([
                'code' => 400,
                'msg' => '未传入任何更新内容',
                'data' => null
            ], 400);
        }

        $news->update($validatedData);

        return response()->json([
            'code' => 200,
            'msg' => '更新成功',
            'data' => $news
        ]);
    }

    /**
     * 删除新闻（管理员专用）
     */
    public function deleteNews(Request $request, $id)
    {
        // 检查是否为管理员
        if (!$request->user() || $request->user()->role !== 1) {
            return response()->json([
                'code' => 403,
                'msg' => '需要管理员权限才能操作',
                'data' => null
            ], 403);
        }

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

    // ==================== 表单草稿管理 ====================

    /**
     * 保存表单草稿
     */
    public function saveDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'form_type' => 'required|string|max:50',
            'config_id' => 'nullable|integer|exists:registration_configs,id',
            'form_data' => 'required|array',
            'current_step' => 'nullable|integer|min:1',
            'total_steps' => 'nullable|integer|min:1',
            'expires_days' => 'nullable|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $deviceId = $request->input('device_id');
        $formType = $request->input('form_type');
        $configId = $request->input('config_id');

        // 查找是否已存在该设备的该类型草稿
        $query = FormDraft::where('device_id', $deviceId)
            ->where('form_type', $formType);
        
        if ($configId) {
            $query->where('config_id', $configId);
        }

        $draft = $query->first();

        $expiresDays = $request->input('expires_days', 7);
        $data = [
            'device_id' => $deviceId,
            'form_type' => $formType,
            'config_id' => $configId,
            'form_data' => $request->input('form_data'),
            'current_step' => $request->input('current_step', 1),
            'total_steps' => $request->input('total_steps', 1),
            'expires_at' => now()->addDays($expiresDays),
        ];

        if ($draft) {
            $draft->update($data);
            $msg = '草稿更新成功';
        } else {
            $draft = FormDraft::create($data);
            $msg = '草稿保存成功';
        }

        return response()->json([
            'code' => 200,
            'msg' => $msg,
            'data' => [
                'draft_id' => $draft->id,
                'form_type' => $draft->form_type,
                'current_step' => $draft->current_step,
                'total_steps' => $draft->total_steps,
                'progress' => $draft->getProgressPercentage() . '%',
                'expires_at' => $draft->expires_at,
                'saved_at' => $draft->updated_at,
            ]
        ]);
    }

    /**
     * 获取表单草稿
     * 根据表单类型获取用户的草稿数据
     */
    public function getDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'form_type' => 'required|string|max:50',
            'config_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $deviceId = $request->input('device_id');
        $formType = $request->input('form_type');
        $configId = $request->input('config_id');

        $query = FormDraft::where('device_id', $deviceId)
            ->where('form_type', $formType);

        if ($configId) {
            $query->where('config_id', $configId);
        }

        $draft = $query->first();

        if (!$draft) {
            return response()->json([
                'code' => 404,
                'msg' => '草稿不存在',
                'data' => null
            ], 404);
        }

        // 检查草稿是否过期
        if ($draft->isExpired()) {
            $draft->delete();
            return response()->json([
                'code' => 410,
                'msg' => '草稿已过期，请重新填写',
                'data' => null
            ], 410);
        }

        return response()->json([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'draft_id' => $draft->id,
                'form_type' => $draft->form_type,
                'config_id' => $draft->config_id,
                'form_data' => $draft->form_data,
                'current_step' => $draft->current_step,
                'total_steps' => $draft->total_steps,
                'progress' => $draft->getProgressPercentage() . '%',
                'expires_at' => $draft->expires_at,
                'created_at' => $draft->created_at,
                'updated_at' => $draft->updated_at,
            ]
        ]);
    }

    /**
     * 获取设备的所有草稿列表（无需登录，使用device_id标识）
     */
    public function getDraftList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'page' => 'nullable|integer|min:1',
            'size' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $deviceId = $request->input('device_id');
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        // 只获取未过期的草稿
        $query = FormDraft::where('device_id', $deviceId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderBy('updated_at', 'desc');

        $list = $query->paginate($size, ['*'], 'page', $page);

        // 格式化数据
        $formattedList = collect($list->items())->map(function ($draft) {
            return [
                'draft_id' => $draft->id,
                'form_type' => $draft->form_type,
                'config_id' => $draft->config_id,
                'current_step' => $draft->current_step,
                'total_steps' => $draft->total_steps,
                'progress' => $draft->getProgressPercentage() . '%',
                'expires_at' => $draft->expires_at,
                'updated_at' => $draft->updated_at,
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
     * 删除表单草稿（无需登录，使用device_id标识）
     */
    public function deleteDraft(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $deviceId = $request->input('device_id');
        $draft = FormDraft::where('id', $id)
            ->where('device_id', $deviceId)
            ->first();

        if (!$draft) {
            return response()->json([
                'code' => 404,
                'msg' => '草稿不存在',
                'data' => null
            ], 404);
        }

        $draft->delete();

        return response()->json([
            'code' => 200,
            'msg' => '草稿删除成功',
            'data' => null
        ]);
    }

    /**
     * 清空设备所有草稿（无需登录，使用device_id标识）
     */
    public function clearAllDrafts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $deviceId = $request->input('device_id');
        $count = FormDraft::where('device_id', $deviceId)->delete();

        return response()->json([
            'code' => 200,
            'msg' => '草稿清空成功',
            'data' => [
                'deleted_count' => $count
            ]
        ]);
    }

    /**
     * 草稿回显接口
     * 用户再次进入页面时，加载已保存的草稿数据
     * 支持根据 device_id + form_type + config_id 精准获取最新草稿
     */
    public function loadDraft(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'form_type' => 'required|string|max:50',
            'config_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $deviceId = $request->input('device_id');
        $formType = $request->input('form_type');
        $configId = $request->input('config_id');

        // 构建查询
        $query = FormDraft::where('device_id', $deviceId)
            ->where('form_type', $formType);

        if ($configId) {
            $query->where('config_id', $configId);
        }

        // 获取最新的草稿（按更新时间倒序）
        $draft = $query->orderBy('updated_at', 'desc')->first();

        // 没有找到草稿
        if (!$draft) {
            return response()->json([
                'code' => 404,
                'msg' => '暂无草稿数据',
                'data' => [
                    'has_draft' => false,
                    'form_data' => null,
                ]
            ]);
        }

        // 检查草稿是否过期
        if ($draft->isExpired()) {
            $draft->delete();
            return response()->json([
                'code' => 410,
                'msg' => '草稿已过期，请重新填写',
                'data' => [
                    'has_draft' => false,
                    'form_data' => null,
                    'expired_at' => $draft->expires_at,
                ]
            ], 410);
        }

        // 返回草稿数据，用于回显
        return response()->json([
            'code' => 200,
            'msg' => '草稿加载成功',
            'data' => [
                'has_draft' => true,
                'draft_id' => $draft->id,
                'form_type' => $draft->form_type,
                'config_id' => $draft->config_id,
                'form_data' => $draft->form_data,
                'current_step' => $draft->current_step,
                'total_steps' => $draft->total_steps,
                'progress' => $draft->getProgressPercentage(),
                'progress_text' => $draft->getProgressPercentage() . '%',
                'expires_at' => $draft->expires_at,
                'updated_at' => $draft->updated_at,
            ]
        ]);
    }
}
