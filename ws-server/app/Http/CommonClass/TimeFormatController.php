<?php


namespace App\Http\CommonClass;


class TimeFormatController
{

    public static function formatSecondToTime(int $_second){
        $_str_time = "";
        $_h = intval($_second/3600);
        $_m = intval(($_second%3600)/60);
        $_s = ($_second%3600)%60;
        if($_h<10){
            $_h = "0".$_h;
        }
        if($_m<10){
            $_m = "0".$_m;
        }
        if($_s<10){
            $_s = "0".$_s;
        }

        return $_h.":".$_m.":".$_s;

    }
}
