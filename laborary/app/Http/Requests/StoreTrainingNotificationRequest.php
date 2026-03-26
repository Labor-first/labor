<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreTrainingNotificationRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check() && Auth::user()?->role === 1;
    }
    public function rules()
    {
        return [
            'title' => 'required|string|max:50',
            'content'=> 'required|string|min:10',
            'target' => 'required|in:all,week,trainee',
            'target_ids' => [
                'required_if:target,week,trainee',
                'array',
                'min:1',
                function ($attribute, $value, $fail) {
                    $target = request()->input('target');
                    if (in_array($target, ['week', 'trainee']) && (is_null($value) || count($value) === 0)) {
                        $fail('当发送对象为指定周次或指定学员时，必须填写目标ID列表');
                    }
                },
            ],
            'target_ids.*' => 'integer',//检查 target_ids 这个数组里的每一个值，确保它们全都是整数
            'send_time_type' => 'required|in:immediate,scheduled',
            'scheduled_time' => 'nullable|date_format:Y-m-d H:i',
            'is_draft' => 'required|boolean',
        ];
    }
    public function messages()
    {
        return [
            'title.required' => '通知标题不能为空',
            'content.required' => '通知内容不能为空',
            'contend.min'=>'通知内容不能小于10个字',
            'target.required' => '发送对象不能为空',
            'target.in' => '发送对象类型不正确',
            'target_ids.required_if' => '当发送对象为指定周次或指定学员时，必须填写目标ID列表',
            'target_ids.min' => '目标ID列表至少包含一个ID',
            'target_ids.*.integer' => '目标ID必须是整数',
            'send_time_type.required' => '发送时间类型不能为空',
            'send_time_type.in' => '发送时间类型不正确',
            'scheduled_time.date_format' => '定时发送时间格式不正确，应为：yyyy-MM-dd HH:mm',
            'is_draft.required' => '草稿状态不能为空',
            'is_draft.boolean' => '草稿状态必须是布尔值',
        ];
    }
}
