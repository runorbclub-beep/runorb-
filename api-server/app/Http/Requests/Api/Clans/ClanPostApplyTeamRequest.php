<?php

namespace App\Http\Requests\Api\Clans;

use Illuminate\Foundation\Http\FormRequest;

class ClanPostApplyTeamRequest extends FormRequest
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
//            'user_id' => "required|integer|exists:usr_user,user_id|unique:user_clans,user_id",
            'title' => "required|unique:user_clans,title",
            // 'clan_avatar' => "required",
            'address' => "required",
            'introduction' => "string"
        ];
    }
}
