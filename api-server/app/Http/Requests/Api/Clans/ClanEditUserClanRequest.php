<?php

namespace App\Http\Requests\Api\Clans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClanEditUserClanRequest extends FormRequest
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
            'id' => "required|integer|exists:user_clans,id",
            'title' => "filled|unique:user_clans,title",
        ];
    }
}
