<?php

namespace App\Http\Requests\Admin\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class BrandAddRequest extends FormRequest
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
            'logo' => 'filled',
            'brand_name' => 'required|min:2|max:30',
            'contact_person' => 'required|min:2|max:10',
            'phone' => 'required|numeric|unique:brands,phone'
        ];
    }
}
