<?php

namespace App\Http\Controllers\PublicFunction;

use Illuminate\Support\Facades\Redis;

class PreventDuplication
{
    //Expire 默认过期时间
    const EXPIRE = 2;

    // prefix
    const REDIS_PREFIX = 'PREVENTDUPLICATION:';


    /**
     * 检测是否重复提交(频繁提交)
     *
     * @param $userId
     * @param $type
     * @param int $exprie
     * @return bool
     */
    public static function check($userId, $type, $exprie = self::EXPIRE)
    {
        $redisName = self::REDIS_PREFIX . $type . $userId;
        if (Redis::Exists($redisName)) {
            return false;
        }
        Redis::set($redisName, 1);
        Redis::Expire($redisName, $exprie);
        return true;
    }


}
