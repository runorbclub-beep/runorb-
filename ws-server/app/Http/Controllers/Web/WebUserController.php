<?php


namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;

/**
 * Created by PhpStorm.
 * User: pengjl
 * Date: 2020/2/26
 * Time: 20:37
 */
class WebUserController extends Controller
{
    public function postLogin(){
//        header("Access-Control-Allow-Origin:http://test.dinuoge.com");
        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_GET
        );
    }

    public function getTest(){
        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>"get测试成功"
        );
    }

    public function postTest(){
        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>"post测试成功"
        );
    }
}