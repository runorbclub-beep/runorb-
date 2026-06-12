<?php


namespace App\Http\Controllers\Api;


use App\Constants\ErrorCode;
use App\Constants\SettingMessage;
use App\Http\CommonClass\Snowflake;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicFunction\LanguageController;
use App\Http\Controllers\PublicFunction\RankController;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Http\Controllers\PublicFunction\SystemErrorController;
use App\Models\UserAchievement;
use App\Models\UserPkList;
use App\Models\UserRankList;
use App\Models\UsrUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redis;

class UserRankListController extends Controller
{


    /**
     * 获取我的排行榜列表
     *
     * @param Request $request
     * @return array
     */
    public function postMyRankingList(Request $request)
    {
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $redisName = 'user_ranking_list';
        Redis::select(1);
        Redis::hdel($redisName, $_usr_user['user_id']);
        if (Redis::hEXISTS($redisName, $_usr_user['user_id'])) {
            $res = Redis::hget($redisName, $_usr_user['user_id']) ?? '';
            $list = $res ? json_decode($res, true) : [];
        } else {
            $_sys_user_type_id = $_usr_user['sys_user_type_id'] ?? '';
            if ($_sys_user_type_id != '1809649560981504') {
                return [
                    "code" => 0,
                    "msg" => trans('messages.please_register_first_error')
                ];
            }
            $_user_age_type = $_usr_user["is_yang"] ?? -1;
            if ($_user_age_type == -1) {
                $_birthday = $_usr_user["birthday"] ?? '';
                $_this_date = strtotime(date("Y-m-d", time()));
                $_age = floor(($_this_date - strtotime($_birthday)) / (60 * 60 * 24 * 365));
                //13-18周岁判定为青少年
                if ($_age >= StaticDataController::$_yang_start_age && $_age <= StaticDataController::$_yang_stop_age) {
                    $_user_age_type = 1;
                } else {
                    $_user_age_type = 0;
                }
            }

            //个人与机构
            $_user_type = $_usr_user['is_group'] ?? 0;
            //城市
            $_address = $_usr_user['address'] ?? '';

            if (!$_address) {
                return [
                    "code" => 0,
                    "msg" => trans('messages.please_fill_in_the_region_first_error')
                ];
            }

            $titleTemp = ($_user_type == 1 ? trans('messages.organization') : trans('messages.individual')) . ($_user_age_type == 1 ? trans('messages.youth') : trans('messages.adult'));

            $sysSex = ($_usr_user['sys_sex_id'] == 1791224340025344 ? trans('messages.male') : ($_usr_user['sys_sex_id'] == 1791224373579776 ? trans('messages.female') : ''));

            if (url()->previous() == StaticDataController::$_server_url_zh){
                $global = trans('messages.global_zh');
                $global_title = trans('messages.global_title_zh');
            }else{
                $global = trans('messages.global_en');
                $global_title = trans('messages.global_title_en');
            }

            $list = [
                [//全球总榜
                    'user_rank_list_id' => '',
                    'title' => $global,
                    'user_age_type' => '',
                    'user_type' => '',
                    'address' => '',
                    'sys_sex_id' => '',
                ],
                [//全球男士性别-机构/个人-青年榜/成年榜
                    'user_rank_list_id' => '',
                    'title' => $global_title . trans('messages.male') . $titleTemp,//$global_title . $sysSex . $titleTemp
                    'user_age_type' => $_user_age_type,
                    'user_type' => $_user_type,
                    'address' => '',
                    'sys_sex_id' => 1791224340025344,// $_usr_user['sys_sex_id'],
                ],
                [//全球女士性别-机构/个人-青年榜/成年榜
                    'user_rank_list_id' => '',
                    'title' => $global_title . trans('messages.female') . $titleTemp,//$global_title . $sysSex . $titleTemp
                    'user_age_type' => $_user_age_type,
                    'user_type' => $_user_type,
                    'address' => '',
                    'sys_sex_id' => 1791224373579776,// $_usr_user['sys_sex_id'],
                ],
                [//地区总榜
                    'user_rank_list_id' => '',
                    'title' => $_address . trans('messages.overall_ranking'), //overall_ranking
                    'user_age_type' => '',
                    'user_type' => '',
                    'address' => $_address,
                    'sys_sex_id' => '',
                ],
                [//地区男士性别-机构/个人-青年榜/成年榜
                    'user_rank_list_id' => '',
                    'title' => $_address . trans('messages.male') . $titleTemp ,//$_address . $sysSex . $titleTemp
                    'user_age_type' => $_user_age_type,
                    'user_type' => $_user_type,
                    'address' => $_address,
                    'sys_sex_id' => 1791224340025344,// $_usr_user['sys_sex_id'],
                ],
                [//地区女士性别-机构/个人-青年榜/成年榜
                    'user_rank_list_id' => '',
                    'title' => $_address . trans('messages.female') . $titleTemp ,//$_address . $sysSex . $titleTemp
                    'user_age_type' => $_user_age_type,
                    'user_type' => $_user_type,
                    'address' => $_address,
                    'sys_sex_id' => 1791224373579776,// $_usr_user['sys_sex_id'],
                ],
            ];

            $res = UserRankList::where('user_id', $_usr_user['user_id'])
                ->where('status', 1)->get(['user_rank_list_id', 'json_data', 'title']);
            $data = $res ? $res->toArray() : [];
            foreach ($data as $da) {
                $thisData = $da['json_data'] ? json_decode($da['json_data'], true) : [];
                $thisData['user_rank_list_id'] = $da['user_rank_list_id'];
                $thisData['title'] = $da['title'];
                $list[] = $thisData;
            }
            $json = $list ? json_encode($list) : '';

            Redis::hset($redisName, $_usr_user["user_id"], $json);
        }

        return [
            "code" => 1,
            "msg" => "ok",
            "data" => [
                "count" => count($list),
                "list" => $list
            ]
        ];
    }


    /**
     * 添加我的排行榜
     *
     * @param Request $request
     * @return array
     */
    public function postMyRankingAdd(Request $request)
    {
        $_data = $request->input();
        //用户年龄类型，0：成年榜，1：青年榜
        $_user_age_type = $_data["user_age_type"] ?? '';
        //个人与机构
        $_user_type = $_data['user_type'] ?? '';
        //城市
        $_address = $_data['address'] ?? '';
        //性别
        $_sys_sex_id = $_data['sys_sex_id'] ?? '';

//        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        if (!$_user_age_type && !$_user_type && !$_address && !$_sys_sex_id) {
//            return SystemErrorController::paramtersError($_language);
            return [
                "code" => 0,
                "msg" => trans('messages.the_configuration_already_exists_error')
            ];
        }

        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $jsonData = json_encode([
            'user_age_type' => $_user_age_type,
            'user_type' => $_user_type,
            'address' => $_address,
            'sys_sex_id' => $_sys_sex_id,
        ]);

        if (url()->previous() == StaticDataController::$_server_url_zh){
            $global = trans('messages.global_zh');
            $global_title = trans('messages.global_title_zh');
        }else{
            $global = trans('messages.global_en');
            $global_title = trans('messages.global_title_en');
        }

        $_arrOfSysNewsData = [
            'title' => ( empty($_address) ? $global_title : $_address) . ($_sys_sex_id == 1791224340025344 ? trans('messages.male') : ($_sys_sex_id == 1791224373579776 ? trans('messages.female') : ''))
                . ($_user_type == 1 ? trans('messages.organization') : ($_user_type == 0 ? trans('messages.individual') : ''))
                . ($_user_age_type == 1 ? trans('messages.youth') : ($_user_age_type == 0 ? trans('messages.adult') : '')).trans('messages.list_ranking'),
            'user_id' => $_usr_user['user_id'],
            'status' => 1,
            'json_data' => $jsonData
        ];

        //判断默认是否存在
        $_myUserType = $_usr_user['is_group'] ?? 0;
        $_myAddress = $_usr_user['address'] ?? '';
        $_myUserAgeType = $_usr_user["is_yang"] ?? -1;
        $_mySysSexId = $_usr_user["sys_sex_id"] ?? '';
        $isHas = $_address == $_myAddress && $_myUserType == $_user_type && $_myUserAgeType == $_user_age_type && $_mySysSexId == $_sys_sex_id;

        if (UserRankList::where('user_id', $_usr_user['user_id'])->where('json_data', $jsonData)->where('status', 1)->count() || $isHas == true) {
            return [
                "code" => 0,
                "msg" => trans('messages.the_configuration_already_exists_error')
            ];
        }

        //已存在ID，编辑
        if (isset($_data["user_rank_list_id"])) {
            UserRankList::where([
                "user_rank_list_id" => $_data["user_rank_list_id"]
            ])->update($_arrOfSysNewsData);
            $_user_rank_list_id = $_data["user_rank_list_id"];
        } else {
            $_sno = new Snowflake(StaticDataController::$_workId);
            $_arrOfSysNewsData["user_rank_list_id"] = $_sno->nextId();
            UserRankList::create($_arrOfSysNewsData);

            $_user_rank_list_id = $_arrOfSysNewsData["user_rank_list_id"];
        }

        Redis::select(1);
        Redis::hdel('user_ranking_list', $_usr_user['user_id']);
        return [
            "code" => 1,
            "msg" => "ok",
            "data" => [
                'user_rank_list_id' => $_user_rank_list_id
            ]
        ];
    }


    /**
     * 删除我的排行榜
     *
     * @param Request $request
     * @return array
     */
    public function postMyRankingDel(Request $request)
    {
        $_user_rank_list_id = $request->get('user_rank_list_id') ?? '';
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';

        if (!$_user_rank_list_id) {
            return SystemErrorController::paramtersError($_language);
        }

        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        UserRankList::where('user_id', $_usr_user['user_id'])
            ->where('user_rank_list_id', $_user_rank_list_id)
            ->update(['status' => -1]);

        Redis::select(1);
        Redis::hdel('user_ranking_list', $_usr_user['user_id']);
        return [
            "code" => 1,
            "msg" => "ok"
        ];
    }

    /**
     * 我的打榜详情
     * @param Request $request
     * @return JsonResponse
     */
    public function myRankingDetails_back(Request $request): JsonResponse
    {
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $list = UserAchievement::selectRaw('IF(speed_max,speed_max,0) AS speed_max,IF(speed_max_time,speed_max_time,0) AS speed_max_time,IF(exponent_molecular,exponent_molecular,0) AS exponent_molecular,IF(exponent_molecular_time,exponent_molecular_time,0) AS exponent_molecular_time,IF(runball_exponent,runball_exponent,0) AS runball_exponent,IF(runball_exponent_time,runball_exponent_time,0) AS runball_exponent_time,IF(marathon,marathon,0) AS marathon,IF(marathon_time,marathon_time,0) AS marathon_time')
            ->where('user_id', $_usr_user['user_id'])
            ->first();

        //统计排名
        $userAchievementCount = UserAchievement::selectRaw("COUNT(CASE WHEN speed_max > ".$list->speed_max." THEN 1 END)+1+IF(speed_max = ".$list->speed_max." AND speed_max_time > ".$list->speed_max_time.",1,0) AS speed_max_count,COUNT(CASE WHEN exponent_molecular > ".$list->exponent_molecular." THEN 1 END)+1+IF(exponent_molecular = ".$list->exponent_molecular." AND exponent_molecular_time > ".$list->exponent_molecular_time.",1,0) AS exponent_molecular_count,COUNT(CASE WHEN runball_exponent > ".$list->runball_exponent." THEN 1 END)+1+IF(runball_exponent = ".$list->runball_exponent." AND runball_exponent_time > ".$list->runball_exponent_time.",1,0) AS runball_exponent_count,COUNT(CASE WHEN marathon < ".$list->marathon." AND marathon > 0 THEN 1 END)+1+IF(marathon = ".$list->marathon." AND marathon_time > ".$list->marathon_time.",1,0) AS marathon_count")->where('user_id', '<>', $_usr_user['user_id'])->first();
        $list = $list->toArray();

        $list['speed_max_time_unix'] = empty($list['speed_max_time']) ? 0 : $list['speed_max_time'];
        $list['exponent_molecular_time_unix'] = empty($list['exponent_molecular_time']) ? 0 : $list['exponent_molecular_time'];
        $list['runball_exponent_time_unix'] = empty($list['runball_exponent_time']) ? 0 : $list['runball_exponent_time'];
        $list['marathon_time_unix'] = empty($list['marathon_time']) ? 0 : $list['marathon_time'];

        $list['speed_max_count'] = empty($list['speed_max']) ? 0 : $userAchievementCount['speed_max_count'];
        $list['speed_max_time'] = empty($list['speed_max_time']) ? 0 : date('Y-m-d H:i:s',$list['speed_max_time']);
        $list['speed_max_unit'] = empty($list['speed_max']) ? 0 : 'rpm';
        $list['exponent_molecular_count'] = empty($list['exponent_molecular']) ? 0 : $userAchievementCount['exponent_molecular_count'];
        $list['exponent_molecular_time'] = empty($list['exponent_molecular_time']) ? 0 : date('Y-m-d H:i:s',$list['exponent_molecular_time']);
        $list['runball_exponent_count'] = empty($list['runball_exponent']) ? 0 : $userAchievementCount['runball_exponent_count'];
        $list['runball_exponent_time'] = empty($list['runball_exponent_time']) ? 0 : date('Y-m-d H:i:s',$list['runball_exponent_time']);
        $list['marathon_count'] = empty($list['marathon']) ? 0 : $userAchievementCount['marathon_count'];
        $list['marathon_time'] = empty($list['marathon_time']) ? 0 : date('Y-m-d H:i:s',$list['marathon_time']);
        $list["exponent_molecular"] = empty($list['exponent_molecular']) ? 0 : (string)round($list["exponent_molecular"] / 1000, 3);
        $list['exponent_molecular_unit'] = empty($list['exponent_molecular']) ? 0 : 'km';
        $list['marathon'] = empty($list['marathon']) ? 0 : RankController::timeFormat($list['marathon']);

        unset($userAchievementCount);
        return $this->success($list);
    }

    /**
     * 我的打榜详情
     * @param Request $request
     * @return JsonResponse
     */
    public function myRankingDetails(Request $request): JsonResponse
    {
        $data = $request->all();
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

//        address  sys_sex_id  user_age_type
        $data['address'] = $data['address'] ?? '';
        $data['sys_sex_id'] = $data['sys_sex_id'] ?? '';
        $data['user_age_type'] = $data['user_age_type'] ?? 0;

        $exponent = RankController::myRankingDetails($data['user_age_type'], 'exponent', '', $data['address'], 'app', 1, 10, $_usr_user['user_id'],$data['sys_sex_id'],$_title = '');
        $max_speed = RankController::myRankingDetails($data['user_age_type'], 'max_speed', '', $data['address'], 'app', 1, 10, $_usr_user['user_id'],$data['sys_sex_id'],$_title = '');
        $onemin = RankController::myRankingDetails($data['user_age_type'], 'onemin', '', $data['address'], 'app', 1, 10, $_usr_user['user_id'],$data['sys_sex_id'],$_title = '');
        $marathon = RankController::myRankingDetails($data['user_age_type'], 'marathon', '', $data['address'], 'app', 1, 10, $_usr_user['user_id'],$data['sys_sex_id'],$_title = '');

        $list = [
            "speed_max" => $max_speed ? $max_speed['value'] : 0,
            "speed_max_time" => $max_speed ? $max_speed['time'] : 0,
            "exponent_molecular" => $onemin ? $onemin['value'] : 0,
            "exponent_molecular_time" => $onemin ? $onemin['time'] : 0,
            "runball_exponent" => $exponent ? $exponent['value'] : 0,
            "runball_exponent_time" => $exponent ? $exponent['time'] : 0,
            "marathon" => $marathon ? $marathon['value'] : 0,
            "marathon_time" => $marathon ? $marathon['time'] : 0,
            "speed_max_time_unix" => $max_speed ? $max_speed['time_unix'] : 0,
            "exponent_molecular_time_unix" => $onemin ? $onemin['time_unix'] : 0,
            "runball_exponent_time_unix" => $exponent ? $exponent['time_unix'] : 0,
            "marathon_time_unix" => $marathon ? $marathon['time_unix'] : 0,
            "speed_max_count" => $max_speed ? $max_speed['index'] : 0,
            "speed_max_unit" => $max_speed ? $max_speed['unit'] : 0,
            "exponent_molecular_count" => $onemin ? $onemin['index'] : 0,
            "runball_exponent_count" => $exponent ? $exponent['index'] : 0,
            "marathon_count" => $marathon ? $marathon['index'] : 0,
            "exponent_molecular_unit" => $onemin ? $onemin['unit'] : 0,
        ];

        return $this->success($list);
    }

    /**
     * 他人的打榜详情
     * @param Request $request
     * @return JsonResponse
     */
    public function othersRankingDetails_back(Request $request): JsonResponse
    {
        $data = $request->all();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        if (empty($data['user_id'])) return $this->error(ErrorCode::SEVER_ERROR,LanguageController::getLanguage($_language, "lack_parameter"));

        $_usr_user = UsrUser::where('user_id',$data['user_id'])->first();
        if (empty($_usr_user)) return $this->error(ErrorCode::SEVER_ERROR,LanguageController::getLanguage($_language, "none_user"));

        $list = UserAchievement::selectRaw('IF(speed_max,speed_max,0) AS speed_max,IF(speed_max_time,speed_max_time,0) AS speed_max_time,IF(exponent_molecular,exponent_molecular,0) AS exponent_molecular,IF(exponent_molecular_time,exponent_molecular_time,0) AS exponent_molecular_time,ROUND(IF(runball_exponent,runball_exponent,0),2) AS runball_exponent,IF(runball_exponent_time,runball_exponent_time,0) AS runball_exponent_time,IF(marathon,marathon,0) AS marathon,IF(marathon_time,marathon_time,0) AS marathon_time')->where('user_id', $_usr_user['user_id'])->first();

        //统计排名
        $userAchievementCount = UserAchievement::selectRaw("COUNT(CASE WHEN speed_max > ".$list->speed_max." THEN 1 END)+1+IF(speed_max = ".$list->speed_max." AND speed_max_time > ".$list->speed_max_time.",1,0) AS speed_max_count,COUNT(CASE WHEN exponent_molecular > ".$list->exponent_molecular." THEN 1 END)+1+IF(exponent_molecular = ".$list->exponent_molecular." AND exponent_molecular_time > ".$list->exponent_molecular_time.",1,0) AS exponent_molecular_count,COUNT(CASE WHEN runball_exponent > ".$list->runball_exponent." THEN 1 END)+1+IF(runball_exponent = ".$list->runball_exponent." AND runball_exponent_time > ".$list->runball_exponent_time.",1,0) AS runball_exponent_count,COUNT(CASE WHEN marathon < ".$list->marathon." AND marathon > 0 THEN 1 END)+1+IF(marathon = ".$list->marathon." AND marathon_time > ".$list->marathon_time.",1,0) AS marathon_count")->where('user_id', '<>', $_usr_user['user_id'])->first();
        $list = $list->toArray();

        $list['speed_max_count'] = empty($list['speed_max']) ? 0 : $userAchievementCount['speed_max_count'];
        $list['speed_max_time'] = empty($list['speed_max_time']) ? 0 : date('Y-m-d H:i:s',$list['speed_max_time']);
        $list['speed_max_unit'] = empty($list['speed_max']) ? 0 : 'rpm';
        $list['exponent_molecular_count'] = empty($list['exponent_molecular']) ? 0 : $userAchievementCount['exponent_molecular_count'];
        $list['exponent_molecular_time'] = empty($list['exponent_molecular_time']) ? 0 : date('Y-m-d H:i:s',$list['exponent_molecular_time']);
        $list['runball_exponent_count'] = empty($list['runball_exponent']) ? 0 : $userAchievementCount['runball_exponent_count'];
        $list['runball_exponent_time'] = empty($list['runball_exponent_time']) ? 0 : date('Y-m-d H:i:s',$list['runball_exponent_time']);
        $list['marathon_count'] = empty($list['marathon']) ? 0 : $userAchievementCount['marathon_count'];
        $list['marathon_time'] = empty($list['marathon_time']) ? 0 : date('Y-m-d H:i:s',$list['marathon_time']);
        $list["exponent_molecular"] = empty($list['exponent_molecular']) ? 0 : (string)round($list["exponent_molecular"] / 1000, 2);
        $list['exponent_molecular_unit'] = empty($list['exponent_molecular']) ? 0 : 'km';
        $list['marathon'] = empty($list['marathon']) ? 0 : RankController::timeFormat($list['marathon']);

        unset($userAchievementCount);
        return $this->success($list);
    }

    public function othersRankingDetails(Request $request): JsonResponse
    {
        $data = $request->all();
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        if (empty($data['user_id'])) return $this->error(ErrorCode::SEVER_ERROR,LanguageController::getLanguage($_language, "lack_parameter"));

        $_usr_user = UsrUser::where('user_id',$data['user_id'])->first();
        if (empty($_usr_user)) return $this->error(ErrorCode::SEVER_ERROR,LanguageController::getLanguage($_language, "none_user"));

        $exponent = RankController::myRankingDetails(0, 'exponent', '', '', 'app', 1, 10, $_usr_user['user_id'],'',$_title = '');
        $max_speed = RankController::myRankingDetails(0, 'max_speed', '', '', 'app', 1, 10, $_usr_user['user_id'],'',$_title = '');
        $onemin = RankController::myRankingDetails(0, 'onemin', '', '', 'app', 1, 10, $_usr_user['user_id'],'',$_title = '');
        $marathon = RankController::myRankingDetails(0, 'marathon', '', '', 'app', 1, 10, $_usr_user['user_id'],'',$_title = '');

        $list = [
            "speed_max" => $max_speed ? $max_speed['value'] : 0,
            "speed_max_time" => $max_speed ? $max_speed['time'] : 0,
            "exponent_molecular" => $onemin ? $onemin['value'] : 0,
            "exponent_molecular_time" => $onemin ? $onemin['time'] : 0,
            "runball_exponent" => $exponent ? $exponent['value'] : 0,
            "runball_exponent_time" => $exponent ? $exponent['time'] : 0,
            "marathon" => $marathon ? $marathon['value'] : 0,
            "marathon_time" => $marathon ? $marathon['time'] : 0,
            "speed_max_time_unix" => $max_speed ? $max_speed['time_unix'] : 0,
            "exponent_molecular_time_unix" => $onemin ? $onemin['time_unix'] : 0,
            "runball_exponent_time_unix" => $exponent ? $exponent['time_unix'] : 0,
            "marathon_time_unix" => $marathon ? $marathon['time_unix'] : 0,
            "speed_max_count" => $max_speed ? $max_speed['index'] : 0,
            "speed_max_unit" => $max_speed ? $max_speed['unit'] : 0,
            "exponent_molecular_count" => $onemin ? $onemin['index'] : 0,
            "runball_exponent_count" => $exponent ? $exponent['index'] : 0,
            "marathon_count" => $marathon ? $marathon['index'] : 0,
            "exponent_molecular_unit" => $onemin ? $onemin['unit'] : 0,
        ];

        return $this->success($list);
    }

    /**
     * 获取用户PK胜率
     * @param Request $request
     * @return JsonResponse
     * User: zxw
     * Date: 2021/11/26 14:10
     */
    public function getUserPkWinRate(Request $request): JsonResponse
    {
        $_user_token = $request->header("token");
        $_usr_user = json_decode(Redis::hget("usr_user", $_user_token), true);

        $list = UserPkList::selectRaw("COUNT(*) AS total,COUNT(IF(user_group=group_win,true,null)) AS victory")
            ->where([
                'user_id' => $_usr_user['user_id'],
                'status' => 1
            ])
            ->first();

        $list['burden'] = $list->total - $list->victory;

        $list['win_rate'] = empty($list->total) ? 0 : round(($list->victory/$list->total)*100,2);

        return $this->success($list);
    }

    /**
     * 获取打榜模式规则简介与PK模式规则简介
     * User: zxw
     * Date: 2021/11/26 15:21
     */
    public function getRuleIntroduce(Request $request): JsonResponse
    {
        $_language = $request->header("language") != null ? $request->header("language") : 'zh-CN';
        App::setLocale($_language);//根据传参设置多语言

        $list = [
            'ranking_rule' => trans('messages.ranking_rule'),
            'pk_rule' => trans('messages.pk_rule'),
        ];
        return $this->success($list);
    }

}
