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
            'user_id' => 'required|string',
            'name'   => 'required|string',
            // 业务字段
            'config_id' => 'required|integer|exists:registration_configs,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '学号不能为空',
            'name.required'   => '姓名不能为空',
            'config_id.required' => '配置ID不能为空',
            'config_id.exists'   => '无效的报名配置',
        ];
    }
}
