<?php

namespace App\Http\Requests\Admin\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class RedeemPostBrandUserPhoneRequest extends FormRequest
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
            'brand_id' => "required|integer|exists:brands,id",
            'brand_shop_id' => "required|integer|exists:brand_shops,id",
            //'phone' => "required|integer|exists:usr_user,phone",
        ];
    }
}
