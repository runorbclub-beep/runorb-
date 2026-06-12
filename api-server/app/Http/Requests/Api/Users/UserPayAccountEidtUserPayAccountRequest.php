<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Foundation\Http\FormRequest;

class UserPayAccountEidtUserPayAccountRequest extends FormRequest
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
            'id' => "required|integer|exists:user_pay_accounts,id",
            'pay_channel_id' => "required|integer|exists:pay_channels,id",
            'account' => 'required',
            'actual_name' => 'required',
//            'account_password' => 'required',
        ];
    }
}
