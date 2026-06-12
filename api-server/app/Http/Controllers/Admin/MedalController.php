<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\fileMoveController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class MedalController extends Controller
{


    /**
     * @author pengjl
     * @abstract 文件上传
     * @return array
     */
    public function getMedalUpload(){
        if(!isset($_FILES["file"])){
            return array(
                "code"=>0,
                "msg"=>"未获取到文件"
            );
        }
        $_file = $_FILES["file"];

//        文件路径处理、移动文件
        $_file_path = fileMoveController::getFilePath("medal_image",$_file["name"]);
        move_uploaded_file($_file["tmp_name"],$_file_path["file_path"]);

        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>array(
                "medal_img_path"=>StaticDataController::$_server_url.'/'.$_file_path["file_path"]
            )
        );
    }

    /**
     * @abstract 创建徽章
     * @param Request $request
     * @return array
     */
    public function postMedalAdd(Request $request){
        $_data = $request->input();

        $_HasMedalImg = isset($_data["HasMedalImg"])?$_data["HasMedalImg"]:"";
        $_unHasMedalImg = isset($_data["unHasMedalImg"])?$_data["unHasMedalImg"]:"";

        $_HasMedalImg = str_replace(StaticDataController::$_server_url,"",$_HasMedalImg);
        $_unHasMedalImg = str_replace(StaticDataController::$_server_url,"",$_unHasMedalImg);

        $_arrOfMedalLevel = array();
        $_arrOfMedalDescription = array();
        foreach ($_data["keys"] as $value){
            array_push($_arrOfMedalLevel,json_decode($_data["medal_level"][$value],true));
            array_push($_arrOfMedalDescription,$_data["medal_description"][$value]);
        }

        $_MedalData = array(
            "HasMedalImg"=>$_HasMedalImg,
            "unHasMedalImg"=>$_unHasMedalImg,
            "medal_name"=>$_data["medal_name"],
            "medal_level"=>$_arrOfMedalLevel,
            "medal_description"=>$_arrOfMedalDescription,
        );


        return array(
            "code"=>1,
            "msg"=>"success",
            "data"=>$_MedalData
        );
    }

}
