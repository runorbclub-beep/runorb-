<?php

namespace App\Http\Requests\Api\Clans;

use Illuminate\Foundation\Http\FormRequest;

class ClanPhotoAddPhotoRequest extends FormRequest
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
            'type' => 'required|integer',
            'user_clan_id' => 'required|integer|exists:user_clans,id',
            'photo_name' => 'filled',
            'photo_url' => 'required',
            'photo_location' => 'filled',
            'sort' => 'filled|integer',
        ];
    }
}
