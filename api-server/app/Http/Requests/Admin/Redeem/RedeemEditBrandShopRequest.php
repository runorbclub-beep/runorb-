<?php

namespace App\Http\Requests\Admin\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class RedeemEditBrandShopRequest extends FormRequest
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
            'shop_name' => "required|min:2|max:150",
            'shop_address' => "required|min:2|max:255",
        ];
    }
}
