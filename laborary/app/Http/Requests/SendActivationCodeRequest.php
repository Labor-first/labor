namespace app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendActivationCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'account' => 'required|string|max:50|exists:users,account'    
        ];
    }
    public function messages()
    {
        return[
            'account.required' => '账号不能为空',
            'account.exists' => '账号不存在'
        ];
    }
}