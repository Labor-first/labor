<?php

namespace app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendHomeworksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 身份验证字段
            'user_id' => 'required|string',
            'name'   => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '学号不能为空',
            'name.required'   => '姓名不能为空',
        ];
    }
}
