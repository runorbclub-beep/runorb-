<?php


namespace App\Services;

use App\Constants\ErrorCode;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\Snowflake;
use App\Http\CommonClass\SnowFlakeSwooles;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\ShakeGroupUser;
use App\Models\UserPlay;
use App\Models\UserPlayDetail;
use Illuminate\Support\Facades\DB;

/**
 * 摇加油Service
 * Class ShakeService
 * @package App\Services
 * User: zxw
 * Date: 2021/9/24 16:19
 */
class ShakeService
{
    /**
     * 获取助力排行
     * @param $param
     * @return array
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/24 17:04
     */
    public function getMyShakeBoostRanking($param): array
    {
        $shake = [];
        $param['limit'] = $param['limit'] ?? 15;

        $shake['my_info'] = ShakeGroupUser::with(['usr_user' => function($query){
            $query->select('user_id','sys_sex_id', 'user_name','user_img');
        }])
            ->where('sys_shake_id', $param['sys_shake_id'])
            ->select('sys_shake_id', 'shake_group_id', 'shake_group_user_id', 'integral', 'distance', 'index', 'title', 'integral_join', 'datetime', 'user_id')
            ->where('user_id', $param['user_id'])
            ->first();
        $shake['my_info']['user_id'] = $shake['my_info']['usr_user']['user_id'] ?? null;
        $shake['my_info']['sys_sex_id'] = $shake['my_info']['usr_user']['sys_sex_id'] ?? null;
        $shake['my_info']['user_name'] = $shake['my_info']['usr_user']['user_name'] ?? null;
        $shake['my_info']['user_img'] = $shake['my_info']['usr_user']['user_img'] ?? null;
        $shake['my_info']['rank_my'] = ShakeGroupUser::where('sys_shake_id', $param['sys_shake_id'])
            ->whereRaw("distance >= ".$shake['my_info']['distance'])
            ->count();

        if (!empty($shake['my_info']['user_img'])) $shake['my_info']['user_img'] = StaticDataController::$_server_url . "/" .$shake['my_info']['user_img'];

        $shake['shake_list'] = ShakeGroupUser::with(['usr_user' => function($query){
            $query->select('user_id','sys_sex_id','user_name', DB::raw("CONCAT('".StaticDataController::$_server_url . "/',user_img) as user_img"));
        }])
            ->select('sys_shake_id', 'shake_group_id', 'shake_group_user_id', 'integral', 'distance', 'index', 'title', 'integral_join', 'datetime', 'user_id')
            ->where('sys_shake_id', $param['sys_shake_id'])
            ->orderByRaw('distance*1 DESC')
            ->paginate($param['limit']);

        $shake['shake_list'] = data_list_format($shake['shake_list']);

        unset($shake['my_info']['usr_user']);
        return $shake;
    }

    /**
     * 根据位置（马号）获取位置详情
     * @param $param
     * @return array
     * @throws BusinessException
     * User: zxw
     * Date: 2021/9/24 17:17
     */
    public function getMyShakeHelpDetail($param): array
    {
        $shake = null;
        $param['limit'] = $param['limit'] ?? 15;

        $shake['my_info'] = ShakeGroupUser::with(['usr_user' => function($query){
            $query->select('user_id','sys_sex_id','user_name','user_img');
        }])
            ->where('sys_shake_id', $param['sys_shake_id'])
            ->where('index', $param['index'])
            ->select('sys_shake_id', 'shake_group_id', 'shake_group_user_id', 'integral', 'distance', 'index', 'title', 'integral_join', 'datetime', 'user_id')
            ->where('user_id', $param['user_id'])
            ->first();
        if (empty($shake['my_info'])){
            $shake['my_info'] = null;
        }else{
            $shake['my_info']['user_id'] = $shake['my_info']['usr_user']['user_id'] ?? null;
            $shake['my_info']['sys_sex_id'] = $shake['my_info']['usr_user']['sys_sex_id'] ?? null;
            $shake['my_info']['user_name'] = $shake['my_info']['usr_user']['user_name'] ?? null;
            $shake['my_info']['user_img'] = $shake['my_info']['usr_user']['user_img'] ?? null;
            $shake['my_info']['rank_my'] = ShakeGroupUser::where('sys_shake_id', $param['sys_shake_id'])
                ->where('index', $param['index'])
                ->whereRaw("distance >= ".$shake['my_info']['distance'])
                ->count();

            if (!empty($shake['my_info']['user_img'])) $shake['my_info']['user_img'] = StaticDataController::$_server_url . "/" .$shake['my_info']['user_img'];
            unset($shake['my_info']['usr_user']);
        }

        $shake['shake_list'] = ShakeGroupUser::with(['usr_user' => function($query){
            $query->select('user_id','sys_sex_id','user_name', DB::raw("CONCAT('".StaticDataController::$_server_url . "/',user_img) as user_img"));
        }])
            ->select('sys_shake_id', 'shake_group_id', 'shake_group_user_id', 'integral', 'distance', 'index', 'title', 'integral_join', 'datetime', 'user_id')
            ->where('sys_shake_id', $param['sys_shake_id'])
            ->where('index', $param['index'])
            ->orderByRaw('distance*1 DESC')
            ->paginate($param['limit']);

        $shake['shake_list'] = data_list_format($shake['shake_list']);
        return $shake;
    }

    /**
     * 补全摇加油数据缺失
     * @param string $param
     * User: zxw
     * Date: 2021/12/02 14:14
     * @return int
     * @throws BusinessException
     */
    public function completionShake(string $param = '')
    {
        $map = [];
        $userPlayMap = [];
        $userPlayDetailMap = [];
        $datetime = '2022-01-06';//(13-p9)

//2021-12-13 "stop_time":"1639332122","start_time":"1639328461";  2021-12-14 "stop_time":"1639418522","start_time":"1639414861";  2021-12-15 "stop_time":"1639504922","start_time":"1639501261";
//2021-12-16 "stop_time":"1639591322","start_time":"1639587661";  2021-12-17 "stop_time":"1639677722","start_time":"1639674061";  2021-12-18 "stop_time":"1639764122","start_time":"1639760461";
//2021-12-19 "stop_time":"1639850522","start_time":"1639846861";  2021-12-20 "stop_time":"1639936922","start_time":"1639933261";  2021-12-21 "stop_time":"1640023322","start_time":"1640019661";
//2021-12-22 "stop_time":"1640109722","start_time":"1640106061";  2021-12-23 "stop_time":"1640196122","start_time":"1640192461";  2021-12-24 "stop_time":"1640282522","start_time":"1640278861";
//2021-12-25 "stop_time":"1640368922","start_time":"1640365261";  2021-12-26 "stop_time":"1640455322","start_time":"1640451661";  2021-12-27 "stop_time":"1640541722","start_time":"1640538061";
//2021-12-28 "stop_time":"1640628122","start_time":"1640624461";  2021-12-29 "stop_time":"1640714522","start_time":"1640710861";  2021-12-30 "stop_time":"1640800922","start_time":"1640797261";
//2021-12-31 "stop_time":"1640887322","start_time":"1640883661";  2022-01-01 "stop_time":"1640973722","start_time":"1640970061";  2022-01-02 "stop_time":"1641060122","start_time":"1641056461";
//2022-01-03 "stop_time":"1641146522","start_time":"1641142861";  2022-01-04 "stop_time":"1641232922","start_time":"1641229261";  2022-01-05 "stop_time":"1641319322","start_time":"1641315661";
//2022-01-06 "stop_time":"1641405722","start_time":"1641402061";  2022-01-07 "stop_time":"1641492122","start_time":"1641488461";  2022-01-08 "stop_time":"1641578522","start_time":"1641574861";

        $jsonData = '{"exponent_molecular":"646.4","is_abnormal":"0","endurance_max":"0","interval":"500","marathon":"0","sys_sys_match_id":"0","stop_time":"1641319322","start_time":"1641315661","created_uid":"81805456728657920","exponent":"21.40","sys_match_id":"0","speed_max":"9060","is_quartets":"0","duration":"1838","distance":"0.01","circle_count":"128191","speed_detail":[],"exponent_denominator":"1812","user_play_id":118535455946838016,"user_play_detail_id":118535455946838017}';

        $jsonData = json_decode($jsonData,true);

        //获取某一天摇加油的数据
        $shake_group_user = ShakeGroupUser::where('datetime',strtotime($datetime))->get();
        $user_arr = $shake_group_user->pluck('user_id');

        //获取参与摇加油用户的运动数据
        $data = UserPlay::selectRaw("user_play_id,user_id,SUM(distance) AS distance")->whereIn('user_id',$user_arr)->whereRaw("FROM_UNIXTIME(stop_time, '%Y-%m-%d')='".$datetime."'")->groupBy('user_id')->get();
        $data_arr = $data->pluck('user_id');

        //计算差集
        $arr_diff = array_diff($user_arr->toArray(),$data_arr->toArray());

        //组装数据
        foreach ($data as $k => $v) {
            foreach ($shake_group_user as $ks => $vs) {
                $_objSnowflake = new Snowflake(StaticDataController::$_workId);
                $jsonData['user_play_id'] = $_objSnowflake->nextId();

                $_objSnowflake2 = new Snowflake(StaticDataController::$_workId);
                $jsonData['user_play_detail_id'] = $_objSnowflake2->nextId();
                $jsonData['created_uid'] = $vs['user_id'];

                if ($v['user_id'] == $vs['user_id']){
                    if ($v['distance'] <= $vs['distance']){
                        $play_distance = sprintf("%.2f",substr(sprintf("%.3f", $v['distance']), 0, -1));
                        $diff = sprintf("%.2f",substr(sprintf("%.3f", $vs['distance'] - $play_distance), 0, -1));
                        $jsonData['distance'] = $diff;
                        if ($diff>0) {
                            $map[] = [
                                'play_distance' => $play_distance,
                                'shake_distance' => $vs['distance'],
                                'diff' => $diff,
                                'uuid' => $vs['user_id'],
                                'json_data' => $jsonData
                            ];
                        }
                    }
                }
            }
        }

        //组装未存储过的用户
        foreach ($shake_group_user as $k=>$v) {
            if (in_array($v['user_id'],$arr_diff)){
                $_objSnowflake = new Snowflake(StaticDataController::$_workId);
                $jsonData['user_play_id'] = $_objSnowflake->nextId();

                $_objSnowflake2 = new Snowflake(StaticDataController::$_workId);
                $jsonData['user_play_detail_id'] = $_objSnowflake2->nextId();
                $jsonData['created_uid'] = $v['user_id'];
                $jsonData['distance'] = $v['distance'];

                $map[] = [
                    'play_distance' => 0,
                    'shake_distance' => $v['distance'],
                    'diff' => $v['distance'],
                    'uuid' => $v['user_id'],
                    'json_data' => $jsonData
                ];
            }
        }

        //组装操作写入数据列表和详情表
        foreach ($map as $k=>$v) {
            //组装运动数据
            $userPlayMap[] = [
                "user_play_id" => $v['json_data']["user_play_id"],
                "source" => 3,
                "status" => 1,
                "duration" => $v['json_data']["duration"],
                "speed_max" => $v['json_data']["speed_max"],
                "circle_count" => $v['json_data']["circle_count"],
                "endurance_max" => $v['json_data']["endurance_max"],
                "compare_last" => 0,
                "start_time" => $v['json_data']["start_time"],
                "stop_time" => $v['json_data']["stop_time"],
                "distance" => $v['json_data']["distance"],
                "user_id" => $v['json_data']["created_uid"],
                "is_abnormal" => $v['json_data']["is_abnormal"],
                "exponent_molecular" => $v['json_data']["exponent_molecular"],//摇跑指数，分子
                "exponent_denominator" => $v['json_data']["exponent_denominator"],//摇跑指数，分母
                "exponent" => $v['json_data']["exponent"],//摇跑指数
                "marathon" => $v['json_data']["marathon"],//摇跑马拉松（全马）
                "created_time" => $v['json_data']["stop_time"],
                "updated_time" => $v['json_data']["stop_time"],
            ];

            $userPlayDetailMap[] = LocalPlayService::userplayDetailHandlePlayLog($v['json_data']);
        }

        try {
            DB::transaction(function () use ($userPlayDetailMap, $userPlayMap) {
                UserPlay::insert($userPlayMap);
                UserPlayDetail::insert($userPlayDetailMap);
            }, 5);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex);
        }

        return count($userPlayMap);
    }

}
