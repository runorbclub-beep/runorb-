<?php

namespace App\Http\Requests\Api\LocalPlay;

use Illuminate\Foundation\Http\FormRequest;

class PostUploadLocalPlayV3Request extends FormRequest
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
            'exponent_molecular' => 'required',
            'endurance_max' => 'required',
            'is_abnormal' => 'required|int',
            'sys_match_id' => 'required|int',
            'sys_sys_match_id' => 'required|int',
            'stop_time' => 'required|int',
            'start_time' => 'required|int',
            'interval' => 'required',
            'created_uid' => 'required',
            'speed_max' => 'required',
            'exponent' => 'required',
            'marathon' => 'required',
            'is_quartets' => 'required|int',
            'duration' => 'required',
            'distance' => 'required',
            'circle_count' => 'required',
            'exponent_denominator' => 'required',
            'speed_detail' => 'required|json',
        ];
    }
}
