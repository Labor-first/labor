<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::with(['user:id,username', 'answerer:id,username']);

        if ($keyword = $request->input('keyword')) {
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('content', 'like', "%{$keyword}%")
                  ->orWhere('answer', 'like', "%{$keyword}%");
            });
        }

        if ($request->has('is_answered')) {
            $isAnswered = $request->input('is_answered');
            if ($isAnswered !== '' && $isAnswered !== null) {
                if ($isAnswered == 1) {
                    $query->whereIn('status', ['answered', 'resolved']);
                } else {
                    $query->where('status', 'pending');
                }
            }
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 10);
        $questions = $query->paginate($perPage);

        $simplifiedData = [
            'current_page' => $questions->currentPage(),
            'data' => $questions->items(),
            'per_page' => $questions->perPage(),
            'total' => $questions->total(),
            'last_page' => $questions->lastPage(),
        ];

        return response()->json([
            'code' => 200,
            'msg' => '获取成功',
            'data' => $simplifiedData
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'answer' => 'nullable|string',
        ], [
            'title.required' => '问题标题不能为空',
            'title.max' => '问题标题不能超过255个字符',
            'content.required' => '问题内容不能为空',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $status = 'pending';
        $answeredBy = null;
        $answeredAt = null;

        if (!empty($request->answer)) {
            $status = 'answered';
            $answeredBy = Auth::id();
            $answeredAt = now();
        }

        $question = Question::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'status' => $status,
            'answer' => $request->answer ?? '',
            'answered_by' => $answeredBy,
            'answered_at' => $answeredAt,
        ]);

        return response()->json([
            'code' => 200,
            'msg' => '问题创建成功',
            'data' => $question->load(['user:id,username', 'answerer:id,username'])
        ], 201);
    }

    public function show($id)
    {
        $question = Question::with(['user:id,username', 'answerer:id,username'])->find($id);

        if (!$question) {
            return response()->json([
                'code' => 404,
                'msg' => '问题不存在',
                'data' => null
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'msg' => '获取成功',
            'data' => $question
        ]);
    }

    public function update(Request $request, $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'code' => 404,
                'msg' => '问题不存在',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'answer' => 'nullable|string',
            'status' => 'sometimes|in:pending,answered,resolved',
        ], [
            'title.required' => '问题标题不能为空',
            'title.max' => '问题标题不能超过255个字符',
            'content.required' => '问题内容不能为空',
            'status.in' => '状态值不正确',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $updateData = [];
        
        if ($request->has('title')) {
            $updateData['title'] = $request->title;
        }
        
        if ($request->has('content')) {
            $updateData['content'] = $request->content;
        }
        
        if ($request->has('answer')) {
            $updateData['answer'] = $request->answer;
            
            if (!empty($request->answer) && $question->status === 'pending') {
                $updateData['status'] = 'answered';
                $updateData['answered_by'] = Auth::id();
                $updateData['answered_at'] = now();
            }
        }
        
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }

        $question->update($updateData);

        return response()->json([
            'code' => 200,
            'msg' => '问题更新成功',
            'data' => $question->load(['user:id,username', 'answerer:id,username'])
        ]);
    }

    public function destroy($id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'code' => 404,
                'msg' => '问题不存在',
                'data' => null
            ], 404);
        }

        $question->delete();

        return response()->json([
            'code' => 200,
            'msg' => '问题已删除',
            'data' => null
        ]);
    }

    public function answerAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.id' => 'required|integer|exists:questions,id',
            'answers.*.answer' => 'required|string',
        ], [
            'answers.required' => '回答数据不能为空',
            'answers.array' => '回答数据格式不正确',
            'answers.*.id.required' => '问题ID不能为空',
            'answers.*.id.exists' => '问题不存在',
            'answers.*.answer.required' => '回答内容不能为空',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $updatedCount = 0;
        $errors = [];
        $adminId = Auth::id();

        foreach ($request->answers as $answerData) {
            try {
                $question = Question::find($answerData['id']);
                if ($question) {
                    $question->update([
                        'answer' => $answerData['answer'],
                        'status' => 'answered',
                        'answered_by' => $adminId,
                        'answered_at' => now(),
                    ]);
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $answerData['id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'code' => 200,
            'msg' => "成功回答 {$updatedCount} 个问题",
            'data' => [
                'updated_count' => $updatedCount,
                'errors' => $errors
            ]
        ]);
    }

    public function batchDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:questions,id',
        ], [
            'ids.required' => '请选择要删除的问题',
            'ids.array' => 'ID格式不正确',
            'ids.*.exists' => '部分问题不存在',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()
            ], 400);
        }

        $deletedCount = Question::whereIn('id', $request->ids)->delete();

        return response()->json([
            'code' => 200,
            'msg' => "成功删除 {$deletedCount} 个问题",
            'data' => null
        ]);
    }
}