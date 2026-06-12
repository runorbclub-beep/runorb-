<?php


namespace App\Http\Controllers\Api\Settings;


use App\Constants\SettingMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * 设置类
 * Class SettingController
 * @package App\Http\Controllers\Api\Settings
 * User: zxw
 * Date: 2021/9/29 9:29
 */
class SettingController extends Controller
{
    protected $introduce = null;//go介绍

    /**
     * GO介绍
     * @return JsonResponse
     * User: zxw
     * Date: 2021/9/29 9:35
     */
    public function setGoIntroduce(): JsonResponse
    {
        $this->introduce['zh-CN'] = SettingMessage::SET_GO_INTRODUCE_ZH_CN;
        $this->introduce['en-US'] = SettingMessage::SET_GO_INTRODUCE_EN_US;
        return $this->success($this->introduce);
    }

    /**
     * 获取APP文案配置信息
     * @return JsonResponse
     * User: zxw
     * Date: 2022/1/26 16:19
     */
    public function getAppSettingInfo(): JsonResponse
    {
        return $this->success($this->introduce);
    }
}
