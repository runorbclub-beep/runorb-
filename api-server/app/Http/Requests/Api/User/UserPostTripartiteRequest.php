<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class UserPostTripartiteRequest extends FormRequest
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
            'open_weixin_id' => 'filled',
            'open_qq_id' => 'filled',
            'open_weibo_id' => 'filled',
            'open_alipay_id' => 'filled',
            'open_ios_id' => 'filled',
            'open_twitter_id' => 'filled',
            'open_facebook_id' => 'filled',
        ];
    }
}
