<?php

namespace App\Http\Controllers\PublicFunction;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use phpDocumentor\Reflection\Types\This;

class RegionController
{

    /**
     * 获取地区
     *
     * @param int $pid
     * @return array|\Illuminate\Support\Collection|mixed
     */
    public static function getRegion($pid = 1)
    {   
        $redisName = 'REGION_LIST:' . $pid;
//        Redis::del($redisName);
        if (Redis::exists($redisName)) {
            $res = Redis::get($redisName) ?? '';
            $newList = $res ? json_decode($res, true) : [];
        } else {
            $list = DB::table('region')->where('path', 'like', $pid . '-%')->get();
            $list = $list ? $list->toArray() : [];
            $newList = [];
            foreach ($list as $li) {
                $li = $li ? (array)$li : [];
                if ($li['pid'] == $pid) {
                    $child = $newList[$li['id']]['child'] ?? [];
                    $newList[$li['id']] = $li;
                    $newList[$li['id']]['child'] = $child;
                } else {
                    $newList[$li['pid']] = $newList[$li['pid']] ?? [];
                    $newList[$li['pid']]['child'][] = $li;
                }
            }

            $newList = self::arraySort($newList, 'order', 'desc');
            $json = $newList ? json_encode($newList) : '';

            Redis::set($redisName, $json);
        }

        return $newList;
    }

    public static function arraySort($arr, $keys, $type = 'desc')
    {
        $key_value = $new_array = array();
        foreach ($arr as $k => $v) {
            $key_value[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($key_value);
        } else {
            arsort($key_value);
        }
        reset($key_value);
        foreach ($key_value as $k => $v) {
            $new_array[] = $arr[$k];
        }
        return $new_array;
    }


}
