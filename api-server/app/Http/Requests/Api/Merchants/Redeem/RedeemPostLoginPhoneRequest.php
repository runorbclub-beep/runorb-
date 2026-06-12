<?php

namespace App\Http\Requests\Api\Merchants\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class RedeemPostLoginPhoneRequest extends FormRequest
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
            'phone' => "required|integer|exists:brand_users,phone",
            'number' => "required|integer"
        ];
    }
}
