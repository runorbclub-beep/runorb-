<?php
/**
 * Created by PhpStorm.
 * User: ns210
 * Date: 2019/12/12
 * Time: 12:04
 */

namespace App\Http\CommonClass;

class SysHttp
{
    /**
     * @abstract 发起http POST 请求，
     * @param $data array 请求内容
     * @param $url string 请求的接口地址
     * @return mixed
     */
    public static function post($data,$url){
        $_headers = array(
            "Content-type:application/json",
        );
        //初使化init方法
        $_dingtalkApiCurl = curl_init();

        //指定URL
        curl_setopt($_dingtalkApiCurl, CURLOPT_URL,$url );

        //设定请求后返回结果
        curl_setopt($_dingtalkApiCurl, CURLOPT_RETURNTRANSFER, 1);

        //声明使用POST方式来进行发送
        curl_setopt($_dingtalkApiCurl, CURLOPT_POST, 1);

        //发送什么数据呢
        curl_setopt($_dingtalkApiCurl, CURLOPT_POSTFIELDS, json_encode($data));

        //忽略证书
        curl_setopt($_dingtalkApiCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($_dingtalkApiCurl, CURLOPT_SSL_VERIFYHOST, false);

        //设置header头信息
        curl_setopt($_dingtalkApiCurl, CURLOPT_HTTPHEADER, $_headers);

        //设置超时时间
        curl_setopt($_dingtalkApiCurl, CURLOPT_TIMEOUT, 10);

        //发送请求
        $output = curl_exec($_dingtalkApiCurl);

        //关闭curl
        curl_close($_dingtalkApiCurl);

        //返回数据
        return $output;

    }

    /**
     * @abstract 发起http POST 请求，
     * @param $data array 请求内容
     * @param $url string 请求的接口地址
     * @return mixed
     */


    /**
     * @abstract 发起http GET 请求，
     * @param $data array 请求内容
     * @param $url string 请求的接口地址
     * @return mixed
     */
    public static function get($data,$url){
        $_headers = array(
            "Content-type:application/json",
        );
        //初使化init方法
        $_curl = curl_init();

        if(count($data)>0){
            $querystring = http_build_query($data);
            $url = $url.'?'.$querystring;
        }

        //指定URL
        curl_setopt($_curl, CURLOPT_URL,$url );

        curl_setopt($_curl, CURLOPT_URL,$url);
        curl_setopt($_curl, CURLOPT_HTTPHEADER,$_headers);
        curl_setopt($_curl, CURLOPT_HEADER, false);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);


        curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
        $output = curl_exec($_curl); //执行并获取HTML文档内容
        curl_close($_curl); //释放curl句柄
        return $output;

    }


}
