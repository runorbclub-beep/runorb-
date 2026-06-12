<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class UserGetAddMatchAwardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required|integer|exists:usr_user,user_id',
            'sys_match_id' => 'required|integer|exists:sys_match,sys_match_id',
            'title' => 'required',
            'title_en' => 'required',
            'award_img' => 'required',
            'back_img' => 'filled',
        ];
    }
}
