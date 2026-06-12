<?php


namespace App\Http\Controllers\PublicFunction;


use App\Exports\ChannelStorePerformanceExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class fileMoveController extends Controller
{
    public static function getFilePath(string $filePathParent,string $fileName){
        $_filePath = $filePathParent.'/'.date("Y",time()).'/'.date("m",time()).'/'.date("Y-m-d",time());

        if(!file_exists($_filePath)){
            mkdir($_filePath,0777,true);
        }

        return array(
            "file_path_parent"=>$_filePath,
            "file_path"=>$_filePath.'/'.$fileName
        );
    }

    public static function getFileMove(string $file_path_parent,string $file_path){
        $_shell = "mv ".base_path()."/storage/app/".$file_path." ".base_path()."/public/".$file_path_parent."/";

        $_status = system($_shell,$return_var);

        return $_status;
    }

}
