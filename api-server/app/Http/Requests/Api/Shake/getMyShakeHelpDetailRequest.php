<?php

namespace App\Http\Requests\Api\Shake;

use Illuminate\Foundation\Http\FormRequest;

class getMyShakeHelpDetailRequest extends FormRequest
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
            'sys_shake_id' => "required|integer|exists:shake_group_user,sys_shake_id",
        ];
    }
}
