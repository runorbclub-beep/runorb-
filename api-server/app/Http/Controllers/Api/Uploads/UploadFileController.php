<?php

namespace App\Http\Controllers\Api\Uploads;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Requests\Api\Uploads\UploadImagesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * 图片、文件上传
 */
class UploadFileController extends Controller
{
    /**
     * 图片“单张”模式上传、“批量”模式上传
     * @param UploadImagesRequest $request
     * @return JsonResponse
     */
    public function uploadImages(UploadImagesRequest $request): JsonResponse
    {
        $data = $request->all();
        $data['images_url'] = $data['images_url'] ?? 'photos';

        $multiple = null;
        $leaflet = null;
        if ($request->hasFile('images')){
            $file = $request->file('images');
            if (is_array($file)){//批量上传模式
                foreach ($file as $files) {
                    $multiple[] = StaticDataController::$_server_url.'/'.Storage::disk('public')->put($data['images_url'],$files);
                }
                $leaflet = $multiple[0];
            }else{//单张上传模式
                $leaflet = StaticDataController::$_server_url.'/'.Storage::disk('public')->put($data['images_url'],$file);
            }
        }
        return $this->success(['leaflet' => $leaflet,'multiple' => $multiple]);
    }
}
