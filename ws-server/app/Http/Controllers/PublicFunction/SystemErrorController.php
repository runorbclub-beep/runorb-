<?php


namespace App\Http\Controllers\PublicFunction;


class SystemErrorController
{

    /**
     * @abstract 参数类型错误
     * @param $_language
     * @return array
     */
    public static function paramtersError($_language){
        return array(
            "code"=>0,
            "msg"=>LanguageController::getLanguage($_language,"lack_parameter")
        );
    }

    /**
     * @abstract 系统数据结构错误
     * @param $_language
     * @return array
     */
    public static function sysDataError($_language){
        return array(
            "code"=>0,
            "msg"=>LanguageController::getLanguage($_language,"sys_data_error")
        );
    }
}
