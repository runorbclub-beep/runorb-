<?php

namespace App\Http\Controllers\Api\PointsWithdrawal;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;

class TestController
{
    public function __construct()
    {
        //设置参数（全局只需设置一次）
        Factory::setOptions($this->getOptions());
    }

    public function getOptions()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = '2021002156660486';//<-- 请填写您的AppId，例如：2019022663440152 -->

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCSFUtdI7vi1se26qVP8Pg/Vv0JXNzMRllDvwIN/cGDov/3Xz2OoLn+kbe8nV8J+fIG8PlS2PA6+zsXcMRlP4BOwfFF/EJKkUOPxrxvoS8lLx6C/zIhGC0A/reuidY/ranXtSNhcycrycJ7TLLMSIDGC3XRiZDi3AdyBWTpvnjlqmIgUxuGLFm/8LcF8xdyNZ2WTtcrSipkro8uFCJqZwFWPJ3TRx0uMAQThNGCyql0NcDpppkVNDJ5tDh2S3UIci4FGsbzy71Yqd3rOPtgcNE966NVXaOwgVlVHE7BPv3O/JuTqsonUa3BuoKRsmPKCkrs21wYfV6ZgSvI2BWeIx+jAgMBAAECggEAbddxKYF6/x+8X7+jua5ZG1dPQEEBDOBAsn3nD5okbdScXubQJHSaJd5vp3U1Rw0XfTyoXDEewVqynfd+1RqgYZfW0WbSebssb+lhOxaZmn4JlTpJ+TRycnMUrjqaTJtKQBXFmrq5U2WLxKZxMsW5fjCT0JB9zvbe6k9AB7neseD9/PcfQyZZ3zoUrWskkETrKBYSlju1AQvk7E2ujT6LKgVEnrvlAgXEuQkCI8wcHHuzpHelH6qGl1Vfry7WCe2lmVqLxRNyTq7G4kxQsXuIpIEBpUmXniJisDo7uj2OSJmozBfcrJv04c5aRbDue1Yhr0/pEg2+PziFaEwMKS520QKBgQDP94MI6Qo9x5Uy5f7R5k0FEsTkr4/3kaiYTx2UBDqMVOIxWEv2Ra2c5OgAWcD6AqmiOo4xf44BzhDw2hmk2nczDHL2Ji7xnOcCk+mqd6fb4X1HXDt6wpTMOfI8eogvnQTCuOY1KeMYRzgjzYJujR+ijf8yQUCixTdv2bJgem6zRQKBgQCz0scNkBXapnP+2r9DBljvjmksbUtHTJT+YcU9n0gvmNEdu/0jSFUmjUm2IbOAH89XvBjShbkb6XLDbN2DNM7rLwPy18craiGltmdP1xbvWv0bbcP4M5l9L3YwYlL5rTmzxhlEZFzcdD9/4RC8KgOT/PpFkmt6FiV9jFSdvsCBxwKBgCo1LMYBLg/t0s0ausX0/Mq7zXQwYYK4cERBQlqJJSzYCXREXF5mM9804hU4Ih9brPv88GEBZ1vca7nGOhAoOqsEqsxkYYCt/ICcbn8ne8z3jcqO4I+AsFxmolA9+ifXsWCn0CkYEDwcMDur+P3g7Hu8X//eGHUwm5i60SYdkxwVAoGAWxIpu4W4e7cHUhApA2HoktJ2E4j6sg5n+vk7Mn1Dys9DQSLfDgppDZBKv5IL3Zy+nrllfOE6oZc2hyDQgs2w6c0y279KYINsrQdXBUlylSBoxYZu1HoVhyANZG23hjmj2pc+XrPRj9jT/AjZN+KzUzSw76E7C2bB7/atOALObisCgYEAvrbDaVF3Chv1Btq0AQqe3GkiSbsKG4wRrsbrU6sRdL9Vcq6jXIyyDI6JRmAInXjBKtFwbA08zg61Nag2elrmU1IXdoLy+MwonqtqoInWJQUfMRHVAsbiNvibjb6ojZxds3FVu2PzUTK4F9/Qas76bx6nwt91rc5ZQ2KsvwzHm+I=';//<-- 请填写您的应用私钥，例如：MIIEvQIBADANB ... ... -->

        $options->alipayCertPath = './../foos/alipayCert/alipayCertPublicKey_RSA2.crt';//<-- 请填写您的支付宝公钥证书文件路径，例如：/foos/alipayCertPublicKey_RSA2.crt -->
        $options->alipayRootCertPath = './../foos/alipayCert/alipayRootCert.crt';//<-- 请填写您的支付宝根证书文件路径，例如：/foos/alipayRootCert.crt" -->
        $options->merchantCertPath = './../foos/alipayCert/appCertPublicKey_2021002156660486.crt';//<-- 请填写您的应用公钥证书文件路径，例如：/foos/appCertPublicKey_2019051064521003.crt -->

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
        // $options->alipayPublicKey = '<-- 请填写您的支付宝公钥，例如：MIIBIjANBg... -->';
        //可设置异步通知接收服务地址（可选）
        $options->notifyUrl = "https://api.runorb.us/api/alipay/notify-url";//<-- 请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback -->

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
        $options->encryptKey = "JpCnu2cYu24QIOx/AB6F7Q==";//<-- 请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA== -->
        return $options;
    }

    public function test()
    {
        //回调的待验签字符串
        $_POST = "charset=UTF-8&biz_content={\"pay_date\":\"2022-01-19 18:30:01\",\"biz_scene\":\"DIRECT_TRANSFER\",\"action_type\":\"FINISH\",\"pay_fund_order_id\":\"20220119110070001506250088945520\",\"origin_interface\":\"alipay.fund.trans.uni.transfer\",\"out_biz_no\":\"ZFB-SJ-JFDH-202201191830008822543732\",\"trans_amount\":\"0.10\",\"product_code\":\"TRANS_ACCOUNT_NO_PWD\",\"order_id\":\"20220119110070000006250063947373\",\"status\":\"SUCCESS\"}&utc_timestamp=1642588201981&sign=c+TSaaIzkA96SP6EzX2EikZJuGVgHnhDc3KKD3Pm+iBEFDJ7vTKWw+Oxnayu6CDWggQ1DU1GpZzqbKC2qHgnyVLyEBJTcslKEp3XxKeXChBEbdZQOd1l88+Yg+b6brnux1T/XEFZIyHV3M1MmUo4kkEZpIFhPugOrecrXi/EY1ttqzQmxoQzwByMsWBGJ/YXduomdYSSD0joE+8E4QFSnIRHNf61adXuGMuGR7zAqmRI01YS6M90n2ud+IMBMmlHWd02IOaVUS+KL4bI4oi6O20W7fAAIJfB2B3Wq2wgT6+rIJKtcqYBjxoEkdgNDTlpUIbIn2SslMyFbQuhFRggUg==&app_id=2021002156660486&version=1.1&sign_type=RSA2&notify_id=2022011900222183001063841410260953&msg_method=alipay.fund.trans.order.changed";
//把字符串通过&符号拆分成数组
        $data = explode('&', $_POST);dd($data);
        $params = array();
        //遍历数组
        foreach ($data as $param) {
            $item = explode('=', $param, "2");
            $params[$item[0]] = $item[1];
        }
//输出拆分后的数据
//print_r($params);
        $result = Factory::payment()->common()->verifyNotify($params);
        if ($result) {
            echo "success";
        } else {
            echo "fail";
        }
        die();
    }

}



