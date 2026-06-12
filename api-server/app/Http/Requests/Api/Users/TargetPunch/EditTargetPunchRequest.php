<?php

namespace App\Http\Requests\Api\Users\TargetPunch;

use Illuminate\Foundation\Http\FormRequest;

class EditTargetPunchRequest extends FormRequest
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
//            'id' => 'required|exists:user_target_punchs,id',
//            'source' => 'required|integer',
            'start_time' => 'required|string',
            'num_month' => 'required|integer|digits_between:1,12',
            'target_distance' => 'required|numeric',
            'min_days' => 'required|integer|digits_between:1,28',
//            'fulfil_days' => 'required|integer|digits_between:1,28',
//            'status' => 'required|integer',
        ];
    }
}
