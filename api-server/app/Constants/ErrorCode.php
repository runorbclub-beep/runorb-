<?php


namespace App\Constants;


class ErrorCode
{
    // 请求成功
    const SEVER_SUCCESS = 1;

    //请求失败 (其中两个接口"pk/join/room";@"match/user/group"返回0为 ‘请求成功’)
    const SEVER_ERROR = 0;

    /********************************************* 10开头为登录部分 ************************************************/
    //token失效
    const ERROR_TOKEN_INVALID = 2;


}
