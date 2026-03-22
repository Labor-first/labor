namespace app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInfoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        $rules = [
            'name' => 'string|max:50|nullable',
            'phone' => 'regex:/^1[3-9]\d{9}$/|nullable',
            'email' => 'email:rfc5322|max:200|nullable'    
        ];
        //若修改密码，需验证原密码和新密码
        if($this->has('new_password')){
            $rules['old_password'] = 'required|string|max:255';
            $rules['new_password'] = 'required|string|max:255|different:old_password';
            //密码复杂度验证（大小写加字母）
            $rules['new_password'].='|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/';
        }
        return $rules;
    }
    public function messages()
    {
        return [
            'phone.regex' => '手机号格式错误',
            'email.email' => '邮箱格式错误',
            'old_password.required' => '原密码不能为空',
            'new_password.required' => '新密码不能与原密码相同',
            'new_password.regex' => '新密码需包含大小写字母和数字'    
        ];
    }
}