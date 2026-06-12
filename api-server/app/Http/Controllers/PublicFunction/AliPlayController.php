<?php


namespace App\Http\Controllers\PublicFunction;


class AliPlayController
{

    public static function getAliPlayInfo($file_id){

        $returnDataFormat = "JSON";
        $vodApiVersion = "2017-03-21";
        $signatureMethod = "HMAC-SHA1";
        $signatureVersion = "1.0";
        $accessKeyId = env("ALI_ACCESS_KEY_ID", "");
        $accessKeySecret = env("ALI_ACCESS_KEY_SECRET", "");

        $h_8_time=time()-8*60*60;
        $_video_info_data = array(
            "Action"=>"GetPlayInfo",
            "VideoId"=>$file_id,
            "Format"=>$returnDataFormat,
            "Version"=>$vodApiVersion,
            "AccessKeyId"=>$accessKeyId,
            "SignatureMethod"=>$signatureMethod,
            "Timestamp"=>date('Y-m-d',$h_8_time).'T'.date('H:i:s',$h_8_time).'Z',
            "SignatureVersion"=>$signatureVersion,
            "SignatureNonce"=>time().rand(1,10000),
        );

        ksort($_video_info_data);

        $StringToSign='GET&'.urlencode('/').'&';
        $q_str='';

        foreach($_video_info_data as $key=>$value){
            $q_str .= urlencode($key).'='.urlencode($value).'&';
        }

        $q_str = substr($q_str,0,-1);
        $StringToSign .= urlencode($q_str);

        $ok_url='https://vod.cn-shenzhen.aliyuncs.com?'.$q_str.'&Signature='.urlencode(base64_encode(hash_hmac("sha1",$StringToSign,$accessKeySecret.'&',true)));

        //初使化init方法
        $_dingtalkApiCurl = curl_init();

        //指定URL
        curl_setopt($_dingtalkApiCurl, CURLOPT_URL,$ok_url );

        //设定请求后返回结果
        curl_setopt($_dingtalkApiCurl, CURLOPT_RETURNTRANSFER, 1);

        //发送请求
        $output = json_decode(curl_exec($_dingtalkApiCurl),true);

        //关闭curl
        curl_close($_dingtalkApiCurl);

//        如果存在数据
        if(isset($output["PlayInfoList"]) && isset($output["PlayInfoList"]["PlayInfo"])){
            return array(
                "play_url"=>$output["PlayInfoList"]["PlayInfo"][0]["PlayURL"],
                "cover_url"=>$output["VideoBase"]["CoverURL"],
            );
        }else{
            return array();
        }
    }


}
