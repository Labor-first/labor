<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectHomeworkRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'score' => 'required|integer|min:0|max:100',
            'comment' => 'nullable|string|max:500',
            'status' => 'required|in:corrected,jected',
        ];
    }
}

?>