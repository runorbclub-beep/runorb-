<?php

namespace App\Http\Requests\Api\Members\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class RedeemGetUsrUserInfoRequest extends FormRequest
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
            'url_token' => 'required'
        ];
    }
}
