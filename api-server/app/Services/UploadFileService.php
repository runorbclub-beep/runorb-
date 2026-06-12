<?php


namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * 图片上传Service
 * Class UploadFileService
 * @package App\Services
 * User: zxw
 * Date: 2021/9/15 16:57
 */
class UploadFileService
{
    /**
     * 图片上传--七牛云/阿里云oss
     * @param $path //要保存的路径（文件夹）
     * @param $file //上传的文件
     * @param string $drive 存储驱动
     * @return false|string 图片完全路径
     */
    public function uploadImage($path, $file, string $drive = 'oss')
    {
        if (!$path) return false;
        //写入本地备份
        Storage::disk('public')->put($path,$file);

        //写入驱动存储
        $disk = Storage::disk($drive);//对特定磁盘上的文件进行操作
        $path = $disk->put($path,$file);
        switch ($drive){
            case 'qiniu':
                return $disk->getUrl($path);
            case 'oss':
                return $disk->url($path);
        }
    }
}
