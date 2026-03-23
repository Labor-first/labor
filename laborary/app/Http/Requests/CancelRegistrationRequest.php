<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 身份验证字段
            'account' => 'required|string',
            'username'   => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => '学号不能为空',
            'username.required'   => '姓名不能为空',
        ];
    }
}
