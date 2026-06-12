<?php

namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Photo;

/**
 * 相片Service
 */
class PhotoService
{
    /**
     * 新增、批量新增相片
     * @param $param
     * @return mixed
     * @throws BusinessException
     */
    public function addPhoto($param)
    {
        try {
            $photo = Photo::insert($param);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.create_error'));
        }
        return $photo;
    }
}
