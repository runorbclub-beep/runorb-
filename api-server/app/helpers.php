<?php

use App\Exceptions\BusinessException;
use App\Constants\ErrorCode;

if (!function_exists('get_request_agreement'))
{
    /**
     * 获取当前http请求协议（http/https）
     * @return string
     * User: zxw
     * Date: 2021/9/14 14:24
     */
    function get_request_agreement(): string
    {
        if ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return 'https://';
        } elseif ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
            return 'https://';
        } elseif ( !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return 'https://';
        }
        return 'http://';
    }
}

if (!function_exists('url_encrypt'))
{
    /**
     * url参数加密方法
     * @param string $data 要加密的字符串
     * @param string $key 加密钥
     * @param int $expire 过期时间 单位 秒
     * @return string|string[]
     * User: zxw
     * Date: 2021/9/14 16:27
     */
    function url_encrypt(string $data, string $key = '#*abc_qyd!', int $expire = 180) {
        $key  = md5(empty($key));
        $data = base64_encode($data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = sprintf('%010d', $expire ? $expire + time():0);
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
        }
        return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
    }
}

if (!function_exists('url_decrypt'))
{
    /**
     * url参数解方法
     * @param string $data 要解的字符串 （必须是encrypt方法加密的字符串）
     * @param string $key 加密密钥
     * @return false|string
     * User: zxw
     * Date: 2021/9/14 16:27
     */
    function url_decrypt(string $data, string $key = '#*abc_qyd!'){
        $key    = md5(empty($key));
        $data   = str_replace(array('-','_'),array('+','/'),$data);
        $mod4   = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        $data   = base64_decode($data);
        $expire = substr($data,0,10);
        $data   = substr($data,10);
        if($expire > 0 && $expire < time()) {//过期，解密失败
            return false;
        }
        $x      = 0;
        $len    = strlen($data);
        $l      = strlen($key);
        $char   = $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            }else{
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return base64_decode($str);
    }
}

if (!function_exists('data_list_format'))
{
    /**
     * 列表数据按前端要求格式化
     * @param $data
     * @return array
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/18 11:51
     */
    function data_list_format($data): array
    {
        $list = null;
        if (is_object($data)) $data = $data->toArray();
        if (!is_array($data)) throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.list_data_conversion_format_error'));

        $list['count'] = $data['total'];//总条数
        $list['totalCount'] = $data['last_page'];//总页数
        $list['currentNo'] = $data['current_page'];//当前页面
        $list['list'] = $data['data'];
        unset($data);
        return $list;
    }
}

if (!function_exists('get_order_number'))
{
    /**
     * 生成唯一订单号
     * @param string $prefix
     * @return string
     * User: zxw
     * Date: 2021/9/23 9:55
     */
    function get_order_number(string $prefix = ''): string
    {
        @date_default_timezone_set("Asia/Shanghai");
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        //生成唯一订单号
        return $prefix.$order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('arraySort'))
{
    /**
     * 二维数组根据某个字段排序
     * @param array $array 要排序的数组
     * @param string $keys   要排序的键字段
     * @param string $sort  排序类型  SORT_ASC     SORT_DESC
     * @return array 排序后的数组
     */
    function arraySort(array $array, string $keys, $sort = SORT_DESC): array
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }
}

if (!function_exists('return_days'))
{
    /**
     * 计算日期增加几天后的日期，假如“2021-08-09”的7天后的日期为“2021-08-16”
     * @param $date_time
     * @param $days
     * @return false|string
     */
    function return_days($date_time,$days)
    {
        return date("Y-m-d",strtotime('+'.$days. 'day',strtotime($date_time)));
    }
}

if (!function_exists('return_months'))
{
    /**
     * 计算日期增加几月后的日期，假如“2021-08-09”的7个月后的日期为“2021-03-09”
     * @param $date_time
     * @param $months
     * @return false|string
     */
    function return_months($date_time,$months)
    {
        return date("Y-m-d",strtotime('+'.$months.'month',strtotime($date_time)));
    }
}

if (!function_exists('num_month_end_day'))
{
    /**
     * 计算日期增加几月后的日期的该月最后一天，假如“2021-08-09”的7个月后的日期为“2022-03-31”
     * @param $date_time
     * @param $months
     * @return false|string
     */
    function num_month_end_day($date_time,$months)
    {
        return date("Y-m-t",strtotime('+'.$months.'month',strtotime($date_time)));
    }
}

if (!function_exists('diff_months'))
{
    /**
     * 计算出两个日期之间的月份
     * @param  [type] $start_date [开始日期，如2014-03]
     * @param  [type] $end_date   [结束日期，如2015-12]
     * @param  string $explode [年份和月份之间分隔符，此例为 - ]
     * @param boolean $addOne    [算取完之后最后是否加一月，用于算取时间戳用]
     * @return array [array] [返回是两个月份之间所有月份字符串]
     *@author Eric
     */
    function diff_months($start_date, $end_date, string $explode='-', bool $addOne): array
    {
        //判断两个时间是不是需要调换顺序
        $start_int = strtotime($start_date);
        $end_int = strtotime($end_date);
        if($start_int > $end_int){
            $tmp = $start_date;
            $start_date = $end_date;
            $end_date = $tmp;
        }

        //结束时间月份+1，如果是13则为新年的一月份
        $start_arr = explode($explode,$start_date);
        $start_year = intval($start_arr[0]);
        $start_month = intval($start_arr[1]);

        $end_arr = explode($explode,$end_date);
        $end_year = intval($end_arr[0]);
        $end_month = intval($end_arr[1]);

        $data = array();
        $data[] = date('Y-m',strtotime($start_date));//第一个月需要日可不用转换格式$start_date

        $tmp_month = $start_month;
        $tmp_year = $start_year;

        //如果起止不相等，一直循环
        while (!(($tmp_month == $end_month) && ($tmp_year == $end_year))) {
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = $tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT);
        }

        if($addOne == true){
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = $tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT);
        }
        return $data;
    }
}

if (!function_exists('getCurl'))
{
    /**
     * CURL的GET方式请求
     * @param $url
     * @return mixed
     * User: zxw
     * Date: 2021/12/22 11:22
     */
    function getCurl($url){
        $headerArray =array("Content-type:application/json;","Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }
}

if (!function_exists('postCurl'))
{
    /**
     * CURL的POST方式请求
     * @param $url
     * @param $data
     * @return mixed
     * User: zxw
     * Date: 2021/12/22 11:22
     */
    function postCurl($url,$data){
        $data  = json_encode($data);
        $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output,true);
    }
}

if (!function_exists('putCurl'))
{
    /**
     * CURL的PUT方式请求
     * @param $url
     * @param $data
     * @return mixed
     * User: zxw
     * Date: 2021/12/22 11:23
     */
    function putCurl($url,$data){
        $data = json_encode($data);
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PUT"); //设置请求方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }
}

if (!function_exists('delCurl'))
{
    /**
     * CURL的DELETE方式请求
     * @param $url
     * @param $data
     * @return mixed
     * User: zxw
     * Date: 2021/12/22 11:23
     */
    function delCurl($url,$data){
        $data  = json_encode($data);
        $ch = curl_init();
        curl_setopt ($ch,CURLOPT_URL,$url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output,true);
    }
}

if (!function_exists('patchCurl'))
{
    /**
     * CURL的PATCH方式请求
     * @param $url
     * @param $data
     * @return mixed
     * User: zxw
     * Date: 2021/12/22 11:23
     */
    function patchCurl($url,$data){
        $data  = json_encode($data);
        $ch = curl_init();
        curl_setopt ($ch,CURLOPT_URL,$url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);     //20170611修改接口，用/id的方式传递，直接写在url中了
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output);
    }
}

if (!function_exists('hide_nickname'))
{
    /**
     * 隐藏用户昵称
     * @param $data
     * @return string
     * User: zxw
     * Date: 2021/12/23 14:48
     */
    function hide_nickname($data): string
    {
        $length = mb_strlen($data);
        return $length >= 2 ? mb_substr($data,0,1)."***".mb_substr($data,-1,1) : $data."***";
    }
}




