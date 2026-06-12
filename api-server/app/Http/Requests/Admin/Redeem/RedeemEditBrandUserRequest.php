<?php

namespace App\Http\Requests\Admin\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class RedeemEditBrandUserRequest extends FormRequest
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
            'id' => "required|integer|exists:brand_users,id",
            'brand_id' => "required|integer|exists:brands,id",
            'brand_shop_id' => "required|integer|exists:brand_shops,id",
            'real_name' => "filled|min:2|max:150",
            'nickname' => "filled|min:2|max:150",
            'email' => "filled|email:rfc,dns",
            'user_img' => 'filled',
        ];
    }
}
