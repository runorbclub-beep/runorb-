<?php


namespace App\Http\Controllers\PublicFunction;


class AppFontController
{
    private static $FontType = array(
        "default"=>array(
            "my_brage_border"=>"",
            "my_item_border"=>"",
            "my_user_img_border"=>"",
            "play_info_total_border"=>"",
            "play_info_detail_border"=>"",
            "my_play_data_border"=>"",
            "my_data_choose_date_border"=>"",
            "my_achievement_border"=>"",
            "home_speed_border"=>"",
            "my_dev"=>"",
            "my_achievement"=>"",
            "my_data"=>"",
            "tabbar"=>""
        )
    );


    /**
     * @abstract 获取移动端样式图片
     * @param String $_font_type
     * @return string[]
     */
    public static function getAppFont(String $_font_type){

        $_base_path = base_path();
        $_arrOfFont = self::$FontType[$_font_type];
        foreach ($_arrOfFont as $key=>$value){
            $value = $_base_path."/public/app_sources/font_type/".$_font_type."/".$key.".png";
            $_arrOfFont[$key] = self::Base64EncodeImage($value);
        }

        return $_arrOfFont;
    }


    /**
     * @param $ImageFile
     * @return bool|string
     */
    public static function Base64EncodeImage($ImageFile) {
        if(file_exists($ImageFile) || is_file($ImageFile)){
            $base64_image = '';
            $image_info = getimagesize($ImageFile);
            $image_data = fread(fopen($ImageFile, 'r'), filesize($ImageFile));
            $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
            return $base64_image;
        }
        else{
            return "";
        }
    }
}
