<?php

namespace App\Http\Requests\Admin\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class RedeemEditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => "required|integer|exists:brands,id",
            'logo' => 'filled',
            'brand_name' => 'filled|min:2|max:30',
            'contact_person' => 'filled|min:2|max:10',
            'phone' => 'filled|numeric'
        ];
    }
}
