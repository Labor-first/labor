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
            'old_password' => '旧密码不能为空',
            'new_password'   => '新密码不能为空',
        ];
    }
}
