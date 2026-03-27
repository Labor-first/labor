<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingWeek;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingWeekController extends Controller
{
    public function index(Request $request)
    {
        $query = TrainingWeek::query();

        if ($keyword = $request->input('keyword')) {
            $query->where('week_name', 'like', "%{$keyword}%");
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        $query->orderBy('start_date', 'desc');

        $perPage = $request->input('per_page', 15);
        $weeks = $query->paginate($perPage);

        $data = $weeks->getCollection()->map(function($week) {
            return [
                'id' => $week->id,
                'week_name' => $week->week_name,
                'start_date' => $week->start_date->format('Y-m-d'),
                'end_date' => $week->end_date->format('Y-m-d'),
                'description' => $week->description,
                'is_published' => $week->is_published,
                'published_at' => $week->published_at?->format('Y-m-d H:i:s'),
                'created_at' => $week->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'week_name' => 'required|string|max:50|unique:training_week,week_name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:500',
        ]);

        $week = TrainingWeek::create([
            'week_name' => $request->week_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
            'is_published' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => '培训周次创建成功',
            'data' => [
                'id' => $week->id,
                'week_name' => $week->week_name,
                'start_date' => $week->start_date->format('Y-m-d'),
                'end_date' => $week->end_date->format('Y-m-d'),
                'description' => $week->description,
                'is_published' => $week->is_published,
                'created_at' => $week->created_at->format('Y-m-d H:i:s'),
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $week = TrainingWeek::findOrFail($id);

        $request->validate([
            'week_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('training_week', 'week_name')->ignore($id)
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:500',
        ]);

        $week->update([
            'week_name' => $request->week_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => '培训周次更新成功',
            'data' => [
                'id' => $week->id,
                'week_name' => $week->week_name,
                'start_date' => $week->start_date->format('Y-m-d'),
                'end_date' => $week->end_date->format('Y-m-d'),
                'description' => $week->description,
                'is_published' => $week->is_published,
                'published_at' => $week->published_at?->format('Y-m-d H:i:s'),
                'updated_at' => $week->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function publish($id)
    {
        $week = TrainingWeek::findOrFail($id);

        if ($week->is_published) {
            return response()->json([
                'success' => false,
                'message' => '该培训周次已发布'
            ], 422);
        }

        $week->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => '培训周次发布成功',
            'data' => [
                'id' => $week->id,
                'week_name' => $week->week_name,
                'is_published' => $week->is_published,
                'published_at' => $week->published_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function show($id)
    {
        $week = TrainingWeek::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $week->id,
                'week_name' => $week->week_name,
                'start_date' => $week->start_date->format('Y-m-d'),
                'end_date' => $week->end_date->format('Y-m-d'),
                'description' => $week->description,
                'is_published' => $week->is_published,
                'published_at' => $week->published_at?->format('Y-m-d H:i:s'),
                'created_at' => $week->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $week->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}