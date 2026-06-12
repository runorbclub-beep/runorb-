<?php


namespace App\Http\Controllers\Api;


use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class DeviceController extends Controller
{


    /**
     * @abstract 获取用户设备列表
     * @param Request $request
     * @return array
     */
    public function postMyDevice(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');
        if ($_user_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }

        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_arrOfUserDevice = UserDevice::where([
            "status" => 1,
            "user_id" => $_usr_user["user_id"]
        ])->select("user_device_id", 'name', "device_uid", "device_name", "status", "user_id", "device_color", "created_time")
            ->distinct("device_name")
            ->orderBy('updated_time','desc')
            ->get();


        foreach ($_arrOfUserDevice as $key => $value) {
            $value["is_select"] = false;
            $_arrOfUserDevice[$key] = $value;
        }

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfUserDevice
        );

    }


    /**
     * @abstract 用户新增设备操作
     * @param Request $request
     * @return array
     */
    public function postMyDeviceAdd(Request $request)
    {
        Redis::select(1);

        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }

        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        if (!isset($_data["device_uid"])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            );
        }

        $_userDevice = UserDevice::where([
            "device_uid" => $_data["device_uid"],
            "user_id" => $_usr_user["user_id"],
            "status" => 1
        ])->select("user_device_id", "user_id", "device_uid", "device_name", "status")->get();

        if (count($_userDevice) == 0) {
            $_snowflake = new Snowflake(StaticDataController::$_workId);

            $_arrOfUserDeviceData = array(
                "user_device_id" => $_snowflake->nextId(),
                "user_id" => $_usr_user["user_id"],
                "status" => 1,
                "device_uid" => $_data["device_uid"],
                "device_name" => isset($_data["device_name"]) ? $_data["device_name"] : $_data["device_uid"],
                "name" => $_data['name'] ?? '',
                "device_color" => $_data['device_color'] ?? '',
            );

            UserDevice::create($_arrOfUserDeviceData);

            return array(
                "code" => 1,
                "msg" => "success",
                "data" => $_arrOfUserDeviceData
            );
        } else {
            return array(
                "code" => 1,
                "msg" => "success",
                "data" => $_userDevice[0]
            );
        }

    }

    /**
     * @abstract 用户删除设备
     * @param Request $request
     * @return array
     */
    public function postMyDeviceDel(Request $request)
    {
        Redis::select(1);
        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }

        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_data = $request->input();

        if (!isset($_data["user_device_id"])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            );
        }


        UserDevice::where([
            "user_id" => $_usr_user["user_id"],
        ])->whereIn("user_device_id", $_data["user_device_id"])->update(["status" => 0]);

//        foreach ($_arrOfUserDevice as $key=>$value){
//            if(in_array($value["user_device_id"],$_data["user_device_id"])){
//                unset($_arrOfUserDevice[$key]);
//            }
//        }
//
//        Redis::hset("user_device",$_usr_user["user_id"],json_encode(array_values($_arrOfUserDevice)));

        return array(
            "code" => 1,
            "msg" => "success"
        );
    }


    /**
     * @abstract 用户编辑设备
     * @param Request $request
     * @return array
     */
    public function postMyDeviceUpdate(Request $request)
    {
        Redis::select(1);

        $_data = $request->input();

        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        $_user_token = $request->header('token');

        if ($_user_token == null) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_token")
            );
        }


        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $_data = $request->input();
        if (!isset($_data["user_device_id"]) || !isset($_data["device_name"]) || !isset($_data["name"])) {
            return array(
                "code" => 0,
                "msg" => LanguageController::getLanguage($_language, "lack_parameter")
            );
        }

        $updateList = [];
        if ($_data["device_name"]) {
            $updateList['device_name'] = $_data["device_name"];
        }

        if ($_data["name"]) {
            $updateList['name'] = $_data["name"];
        }

        if (!empty($_data["device_color"])) {
            $updateList['device_color'] = $_data["device_color"];
        }

        if ($updateList) {
            UserDevice::where([
                "user_id" => $_usr_user["user_id"],
                "user_device_id" => $_data["user_device_id"]
            ])->update($updateList);
        }
//        $_arrOfUserDevice = json_decode(Redis::hget("user_device",$_usr_user["user_id"]),true);
//
//        if($_arrOfUserDevice==null){
//            $_arrOfUserDevice = UserDevice::where([
//                "status"=>1,
//                "user_id"=>$_usr_user["user_id"]
//            ])->select("user_device_id","device_uid","device_name","status","user_id")->get();
//        }
//
//        $_indexOfUpdateDevice = 0;
//        $_arrOfUserDeviceData = array();
//        foreach ($_arrOfUserDevice as $key=>$value){
//            if($value["user_device_id"]==$_data["user_device_id"]){
//                $_arrOfUserDeviceData = $_arrOfUserDevice[$key];
//                $_indexOfUpdateDevice = $key;
//
////                编辑信息
//                UserDevice::where([
//                    "user_device_id"=>$value["user_device_id"]
//                ])->update([
//                    "device_name"=>$_data["device_name"]
//                ]);
//            }
//        }
//
//        if(isset($_data["device_name"])&&$_data["device_name"]!=$_arrOfUserDeviceData["device_name"]){
//            $_arrOfUserDeviceData["device_name"] = $_data["device_name"];
//        }
//
//        $_arrOfUserDevice[$_indexOfUpdateDevice] = $_arrOfUserDeviceData;
//
//        Redis::hset("user_device",$_usr_user["user_id"],json_encode($_arrOfUserDevice));


        return array(
            "code" => 1,
            "msg" => "success"
        );
    }

}
