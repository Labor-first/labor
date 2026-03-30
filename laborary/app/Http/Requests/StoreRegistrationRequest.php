<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 权限检查在控制器中通过 verifyStudentCredentials 处理，这里直接放行
        return true;
    }

    public function rules(): array
    {
        return [
            // 身份验证字段
            'user_id' => 'required|string',
            'name'   => 'required|string',

            // 业务字段
            'config_id'     => 'required|integer|exists:registration_configs,id',
            'class'         => 'required|string|max:50',
            'academy'       => 'required|string|max:100',
            'major'         => 'required|string|max:100',
            'email'         => 'required|string|email|max:100',
            'director_name' => 'required|string|max:50',
            'sign_reason'   => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '学号不能为空',
            'name.required'   => '姓名不能为空',
            'config_id.required'  => '配置ID不能为空',
            'config_id.exists'    => '无效的报名配置',
            'class.required'      => '班级不能为空',
            'academy.required'    => '学院不能为空',
            'major.required'      => '专业不能为空',
            'email.required'      =>'邮箱不能为空',
            'director_name.required' => '导员姓名不能为空',
            'sign_reason.required'   => '申请理由不能为空',
            'sign_reason.min'        => '申请理由不能少于10个字',
            'sign_reason.max'        => '申请理由不能超过500个字',
        ];
    }
}
