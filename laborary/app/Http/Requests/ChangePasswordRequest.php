<?php

namespace app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            // 身份验证字段
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.required' => '旧密码不能为空',
            'old_password.min' => '旧密码至少6位',
            'new_password.required' => '新密码不能为空',
            'new_password.min' => '新密码至少6位',
            'new_password.confirmed' => '两次输入的新密码不一致',
        ];
    }
}
