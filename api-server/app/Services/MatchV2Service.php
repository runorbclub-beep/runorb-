<?php


namespace App\Services;

use App\Constants\ErrorCode;
use App\Constants\SettingMessage;
use App\Exceptions\BusinessException;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\MatchsAward;
use App\Models\MatchsStage;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\SysMatch;
use App\Models\UsrUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use function Swoole\Coroutine\Http\get;

/**
 * 赛事改版Service
 * Class MatchV2Service
 * @package App\Services
 * User: zxw
 * Date: 2021/10/12 15:04
 */
class MatchV2Service
{
    /**
     * 根据状态获取赛事列表
     * @param $param
     * @return mixed
     * @throws BusinessException
     * User: zxw
     * Date: 2021/10/12 15:33
     */
    public function getSysMatchList($param)
    {
        $param['limit'] = $param['limit'] ?? 15;

        switch ($param['type']){
            case 1://未开始 match_image_list
                $sysMatch = SysMatch::select('sys_match_id','matchs_type_id','sys_sys_match_id','matchs_event_type_id',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',match_image) as match_image,CONCAT('".StaticDataController::$_server_url . "/',match_image_list) as match_image_list"),'match_title','match_title_en','match_champion_prize','match_start_time','match_stop_time','match_status','join_status','is_hot','is_group','match_user_sign_count','match_champion_prize_description','match_user_type_description','match_user_sex_description')
                    ->withCasts([
                        'match_start_time' => 'datetime:Y.m.d',
                        'match_stop_time' => 'datetime:Y.m.d',
                    ])
                    ->withCount('sys_matchs_users as user_num')
                    ->with(['matchs_stages_one'=>function($query){
                        $query->where('status',1)->where('matchs_stage_status',1);
                        $query->select('matchs_stage_id','sys_match_id','match_stage_title','match_stage_title_en','view_type');
                        $query->orderBy('match_stage_start_time','asc');
                    }])
                    ->where('sys_sys_match_id',null)
                    ->where('match_status',1)
                    ->where('website_show',1)
                    ->orderBy('match_start_time','desc')
                    ->orderBy('is_hot','desc')
                    ->paginate($param['limit']);
                break;
            case 2://进行中
                $sysMatch = SysMatch::select('sys_match_id','matchs_type_id','sys_sys_match_id','matchs_event_type_id',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',match_image) as match_image,CONCAT('".StaticDataController::$_server_url . "/',match_image_list) as match_image_list"),'match_title','match_title_en','match_champion_prize','match_start_time','match_stop_time','match_status','join_status','is_hot','is_group','match_user_sign_count','match_champion_prize_description','match_user_type_description','match_user_sex_description')
                    ->withCasts([
                        'match_start_time' => 'datetime:Y.m.d',
                        'match_stop_time' => 'datetime:Y.m.d',
                    ])
                    ->withCount('sys_matchs_users as user_num')
                    ->with(['matchs_stages_one'=>function($query){
                        $query->where('status',1)->where('matchs_stage_status',2);
                        $query->select('sys_match_id','match_stage_title','match_stage_title_en');
                        $query->orderBy('match_stage_start_time','asc');
                    }])
                    ->where('sys_sys_match_id',null)
                    ->where('website_show',1)
                    ->where('match_status',2)
                    ->orderBy('match_start_time','desc')
                    ->orderBy('is_hot','desc')
                    ->paginate($param['limit']);
                break;
            case 3://已结束
                $sysMatch = SysMatch::select('sys_match_id','matchs_type_id','sys_sys_match_id','matchs_event_type_id',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',match_image) as match_image,CONCAT('".StaticDataController::$_server_url . "/',match_image_list) as match_image_list"),'match_title','match_title_en','match_champion_prize','match_start_time','match_stop_time','match_status','join_status','is_hot','is_group','match_user_sign_count','match_champion_prize_description','match_user_type_description','match_user_sex_description')
                    ->withCasts([
                        'match_start_time' => 'datetime:Y.m.d',
                        'match_stop_time' => 'datetime:Y.m.d',
                    ])
                    ->withCount('sys_matchs_users as user_num')
                    ->with(['matchs_stages_one'=>function($query){
                        $query->where('status',1)->where('matchs_stage_status',3);
                        $query->select('sys_match_id','match_stage_title','match_stage_title_en');
                        $query->orderBy('match_stage_stop_time','desc');
                    }])
                    ->where('sys_sys_match_id',null)
                    ->where('match_status',3)
                    ->where('website_show',1)
                    ->orderBy('match_stop_time','desc')
                    ->orderBy('is_hot','desc')
                    ->paginate($param['limit']);
                break;
            default://非法请求
                throw new BusinessException(ErrorCode::SEVER_ERROR, trans('messages.error_illegal_request'));
        }
        return $sysMatch;
    }

    /**
     * 根据赛事ID获取赛事详情
     * @param $param
     * @return mixed
     * User: zxw
     * Date: 2021/10/13 10:57
     */
    public function getSysMatchDetails($param)
    {
        $sysMatch = SysMatch::select('sys_match_id','matchs_type_id','sys_sys_match_id','matchs_event_type_id',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',match_image) as match_image,CONCAT('".StaticDataController::$_server_url . "/',match_image_list) as match_image_list"),'match_title','match_title_en','match_champion_prize','match_start_time','match_stop_time','match_status','join_status','is_hot','is_group','match_user_sign_count','match_champion_prize_description','match_user_type_description','match_user_sex_description')
            ->withCasts([
                'match_start_time' => 'datetime:Y.m.d',
                'match_stop_time' => 'datetime:Y.m.d',
            ])
            ->withCount('matchs_users as user_num')
            ->with(['matchs_stages'=>function($query){
                $query->where('status',1);
                $query->select('sys_match_id','match_stage_title','match_stage_title_en','match_stage_start_time','match_stage_stop_time','matchs_stage_status');
            }])
            ->where('matchs_event_type_id',SettingMessage::matchs_event_type_id)//摇跑四项赛-赛事项目类型ID
            ->first();
        return $sysMatch;
    }

    /**
     * 关联赛事插入假数据
     *
     * @param $param
     * User: zxw
     * Date: 2021/10/27 9:25
     */
    public function sysMatchInsertFakeData($param)
    {
        //sys_match_id:63780668731035648
        //sys_sys_match_id:63780527856947200
        //matchs_stage_id1:63783531456761856
        //matchs_stage_id2:63784088980426752

        $_sno = new Snowflake(StaticDataController::$_workId);
            //获取随机10名用户
            $user = UsrUser::where('status',1)->where('sys_user_type_id',1809649523232768)->inRandomOrder()->take(10)->get();

        DB::transaction(function () use ($_sno, $param, $user) {
            //组装数据
            foreach ($user as $k=>$v){
                $time = rand(1625104509,1626533709);//报名时间区间
                $time2 = rand(1626570306,1627228746);//第一赛段
                $time3 = rand(1627781520,1627783080);//第二赛段
                $matchsUserId = $_sno->nextId();

                MatchsUser::insert([//赛事报名表
                    'matchs_user_id' => $matchsUserId,
                    'sys_match_id' => $param['sys_match_id'],//赛事项目ID
                    'user_id' => $v['user_id'],
                    'created_time' => $time,
                    'updated_time' => $time,
                    'status' => 1,
                    'is_join' => 1,
                    'sys_sys_match_id' => $param['sys_sys_match_id'],//赛事ID
                    'user_name' => $v['user_name'],
                    'stage_pass' => 1,//是否允许进入赛段
                ]);
                //第一赛段
                MatchsUserGrade::insert([//用户赛事成绩
                    'matchs_user_grade_id' => $_sno->nextId(),
                    'matchs_stage_id' => $param['matchs_stage_id1'],
                    'user_id' => $v['user_id'],
                    'matchs_user_id' => $matchsUserId,
                    'created_time'  => $time,
                    'updated_time'  => $time2,
                    'match_grade' => rand(2082,4300),
                    'is_group' => 0,
                ]);
                //第二赛段
                MatchsUserGrade::insert([//用户赛事成绩
                    'matchs_user_grade_id' => $_sno->nextId(),
                    'matchs_stage_id' => $param['matchs_stage_id2'],
                    'user_id' => $v['user_id'],
                    'matchs_user_id' => $matchsUserId,
                    'created_time'  => $time3,
                    'updated_time'  => $time3,
                    'match_grade' => rand(2082,4300),
                    'is_group' => 0,
                ]);
            }
        },5);

        return $user;
    }

    /**
     * 1、例赛==获取例赛列表
     *
     * @param $param
     * @return mixed
     */
    public function getRegularSeasonList($param)
    {
        $param['limit'] = $param['limit'] ?? 15;

        return SysMatch::join("sys_match as sys_sys_match", function ($join) {
                $join->on("sys_match.sys_match_id", "=", "sys_sys_match.sys_sys_match_id");
            })
            ->select('sys_match.sys_match_id','sys_sys_match.sys_match_id AS sys_sys_match_id','sys_match.match_title','sys_match.match_title_en','sys_match.regular_title','sys_match.regular_title_en','sys_match.match_start_time','sys_match.match_stop_time','sys_match.match_status','sys_match.join_status','sys_match.is_hot',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',sys_match.match_image_list_new) as match_image_list_new"))
            ->withCount('sys_matchs_users as user_num')
            ->where('sys_match.sys_sys_match_id',null)
            ->where('sys_match.is_regular',1)
            ->where('sys_match.status',1)
            ->whereRaw("FROM_UNIXTIME(sys_match.match_start_time,'%Y')=FROM_UNIXTIME('".$param['s_year']."','%Y')")
//            ->orderByRaw("FIELD(match_status,2,1,3)")
            ->orderBy('sys_match.match_start_time','desc')
//            ->orderBy('is_hot','desc')
            ->paginate($param['limit']);
    }

    /**
     * 2、例賽==獲取例賽勛章
     *
     * @param $param
     * @return LengthAwarePaginator
     */
    public function getMatchsAwardList($param): LengthAwarePaginator
    {
        $param['limit'] = $param['limit'] ?? 15;

        return MatchsAward::leftJoin('sys_match','matchs_awards.sys_match_id','sys_match.sys_match_id')
            ->join("sys_match as sys_sys_match", function ($join) {
                $join->on("sys_match.sys_match_id", "=", "sys_sys_match.sys_sys_match_id");
            })
            ->select('matchs_awards.id', 'matchs_awards.user_id', 'matchs_awards.sys_match_id', 'sys_match.sys_match_id','sys_sys_match.sys_match_id AS sys_sys_match_id', 'matchs_awards.title', 'matchs_awards.title_en','sys_match.match_title','sys_match.match_title_en','sys_match.regular_title','sys_match.regular_title_en','sys_match.match_start_time',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',sys_match.match_image_list_new) as match_image_list_new"),DB::raw("CONCAT('".StaticDataController::$_server_url . "/',matchs_awards.award_img) as award_img"),DB::raw("CONCAT('".StaticDataController::$_server_url . "/',matchs_awards.back_img) as back_img"))//'matchs_awards.award_img', 'matchs_awards.back_img',
            ->where('matchs_awards.user_id',$param['user_id'])
            ->where('sys_match.is_regular',1)
            ->where('sys_match.status',1)
            ->whereRaw("FROM_UNIXTIME(sys_match.match_start_time,'%Y')=FROM_UNIXTIME('".$param['s_year']."','%Y')")
            ->orderBy('sys_match.match_start_time','desc')
            ->paginate($param['limit']);
    }

    /**
     * 3、例賽==獲取例賽赛点
     *
     * @param $param
     * @return mixed
     */
    public function getMatchPointList($param)
    {
        $param['limit'] = $param['limit'] ?? 15;

        return MatchsUser::leftJoin('sys_match','matchs_user.sys_sys_match_id','sys_match.sys_match_id')
            ->join("sys_match as sys_sys_match", function ($join) {
                $join->on("sys_match.sys_match_id", "=", "sys_sys_match.sys_sys_match_id");
            })
            ->select('matchs_user.matchs_user_id', 'matchs_user.user_id', 'matchs_user.s_match_point', 'sys_match.sys_match_id','sys_sys_match.sys_match_id AS sys_sys_match_id','sys_match.match_title','sys_match.match_title_en','sys_match.regular_title','sys_match.regular_title_en','sys_match.match_start_time',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',sys_match.match_image_list_new) as match_image_list_new"))
            ->where('matchs_user.user_id',$param['user_id'])
            ->where('sys_match.is_regular',1)
            ->where('matchs_user.s_match_point','>',0)
            ->where('sys_match.status',1)
            ->whereRaw("FROM_UNIXTIME(sys_match.match_start_time,'%Y')=FROM_UNIXTIME('".$param['s_year']."','%Y')")
            ->orderBy('sys_match.match_start_time','desc')
            ->paginate($param['limit']);
    }

    /**
     * 全部我的赛事==获取我参与的全部赛事
     *
     * @param $param
     * @return mixed
     * @throws BusinessException
     */
    public function getMyAllMatchList($param)
    {
        $param['limit'] = $param['limit'] ?? 15;
        $map = [
            'sys_match.sys_sys_match_id' => null,
            'sys_match.status' => 1,
            'matchs_user.status' => 1,
            'matchs_user.is_join' => 1,
            'matchs_user.user_id' => $param['user_id']
        ];

        $param['s_type'] == 2 ? $map['sys_match.is_regular'] = 0 : '';
        $param['s_type'] == 3 ? $map['sys_match.is_regular'] = 1 : '';

        $getMyAllMatchList = SysMatch::join("sys_match as sys_sys_match", function ($join) {
            $join->on("sys_match.sys_match_id", "=", "sys_sys_match.sys_sys_match_id");
        })->join("matchs_user", function ($join) {
            $join->on("sys_match.sys_match_id", "=", "matchs_user.sys_sys_match_id");
        })
            ->select('sys_match.sys_match_id','sys_sys_match.sys_match_id AS sys_sys_match_id','sys_match.match_title','sys_match.match_title_en','sys_match.regular_title','sys_match.regular_title_en','sys_match.match_start_time','sys_match.match_stop_time','sys_match.match_status','sys_match.join_status','sys_match.is_hot',DB::raw("CONCAT('".StaticDataController::$_server_url . "/',sys_match.match_image_list_new) as match_image_list_new"),'matchs_user.matchs_user_id','matchs_user.user_id','matchs_user.user_group_id','matchs_user.is_group','matchs_user.user_group_finish_time','matchs_user.user_name','matchs_user.user_group_name','matchs_user.team_tag','matchs_user.is_quartets','matchs_user.s_match_point')
            ->withCount('sys_matchs_users as user_num')
            ->where($map);

        if ($param['s_type'] == 3){
            if (empty($param['s_year'])){
                throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.request_parameter_error'));
            }
            $getMyAllMatchList = $getMyAllMatchList->whereRaw("FROM_UNIXTIME(sys_match.match_start_time,'%Y')=FROM_UNIXTIME('".$param['s_year']."','%Y')");
//            ->orderByRaw("FIELD(match_status,2,1,3)")
        }

        return $getMyAllMatchList->orderBy('sys_match.match_start_time','desc')
//            ->orderBy('is_hot','desc')
            ->paginate($param['limit']);
    }

    /**
     * 赛事浏览人数写入
     * @param $sys_match_id 赛事ID
     * @param $user_id 用户ID
     * @return bool
     * User: zxw
     * Date: 2022/4/12 16:46
     */
    public static function setMatchBrowseNum($sys_match_id,$user_id): bool
    {
        Redis::select(13);
        if (Redis::hexists('browse_nums_'.$sys_match_id,$user_id)) return true;
        try {
            SysMatch::where('sys_match_id',SysMatch::where('sys_match_id',$sys_match_id)->value('sys_sys_match_id'))->increment('browse_num',1);
        }catch (\Throwable $ex){
            return false;
        }
        Redis::hset('browse_nums_'.$sys_match_id,$user_id,1);
        Redis::incr('browse_num_'.$sys_match_id);
        return true;
    }


}
