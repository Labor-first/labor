namespace app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'account' => 'required|string|max:50',
            'password' => 'required|string|max:50',
            'remember_me' => 'boolean|nullable'
        ]
    }
    public function messages()
    {
        return[
            'account.required' => '账号不能为空',
            'password.required' => '密码不能为空',
            'remember_me.boolean' => '记住登录必须为布尔值'
        ];
    }
}