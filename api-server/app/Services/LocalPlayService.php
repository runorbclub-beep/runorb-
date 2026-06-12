<?php


namespace App\Services;

use App\Constants\ErrorCode;
use App\Constants\SettingMessage;
use App\Exceptions\BusinessException;
use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\MatchsUserGrade;
use App\Models\PostPlayLog;
use App\Models\SysMatch;
use App\Models\SysShake;
use App\Models\UserAchievement;
use App\Models\UserClanMember;
use App\Models\UserPkList;
use App\Models\UserPlay;
use App\Models\UserPlayDetail;
use App\Models\UsrUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 本地数据上传
 * Class LocalPlayService
 * @package App\Services
 * User: zxw
 * Date: 2021/10/22 11:33
 */
class LocalPlayService
{
    /**
     * 运动数据上传提交
     * User: zxw
     * Date: 2021/10/22 11:49
     * @throws BusinessException
     */
    public function postplaylog($param)
    {
        try {
            $list = PostPlayLog::create($param);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR,trans('messages.create_error'));
        }
        return $list;
    }

    /**
     * 处理上传的运动数据--队列调用
     * @param $param
     * User: zxw
     * Date: 2021/10/23 14:15
     * @throws BusinessException
     */
    public function handlePlayLog($param)
    {
        $matchsUserGrade = [];
        $aberrant = [];
        $Ismatchs = 0;//默认不是竞标赛

        //查询数据是否被处理过
        $postPlayLog = PostPlayLog::where([
            'post_play_id'=>$param->post_play_id,
            'status' => 1
        ])->first();

        //不存在，直接返回false结束
        if (!$postPlayLog) return false;

        //json数据转数组
        $jsonData = json_decode($postPlayLog->json_data,true);
        
        // 补齐字段
        if (empty($jsonData['exponent_speed_max'])) {
            $jsonData['exponent_speed_max'] = 0;
        }
        

        //修补前端赛事半马数据提交缺失指数、半马距离、耗时的bug（修补条件满足：distance>2100&&exponent_denominator=0&&source=4&&is_quartets=1&&ranking_type_list=3）
        if ($jsonData['distance'] > 21000 && empty($jsonData['exponent_denominator']) && $jsonData['source'] == 4 && $jsonData['is_quartets'] == 1 && !empty($jsonData['sys_sys_match_id'])){
            $ranking_type_list = SysMatch::where('sys_match_id',$jsonData['sys_sys_match_id'])->value('ranking_type_list');
            if ($ranking_type_list == 3){
                $jsonData['distance'] =  21098;
                $jsonData['exponent_denominator'] = $jsonData['duration'] = $jsonData['duration']+1;
            }
        }

        //重新计算摇跑指数
        if (!empty($jsonData['exponent_speed_max']) && !empty($jsonData['exponent_molecular'])){
            // $jsonData["exponent"] = round($jsonData["exponent_molecular"]/($jsonData["exponent_denominator"]/60),2); // 摇跑指数计算
            $jsonData["exponent"] = round($jsonData['exponent_speed_max'] * $jsonData['exponent_molecular'] / 1000000,2); // 活力指数计算(摇跑1分钟内的最高转速x摇跑1分钟内的距离再除以1000=活力指数)
        }else{
            $jsonData["exponent"] = 0;
        }
        
        $jsonData['is_quartets'] = $jsonData['is_quartets'] ?? 0;
        $jsonData['sys_match_id'] = $jsonData['sys_match_id'] ?? 0;
        $jsonData['sys_sys_match_id'] = $jsonData['sys_sys_match_id'] ?? 0;

        //处理异常，重复提交问题
        if (UserPlay::where('user_id',$jsonData['created_uid'])->where('start_time',$jsonData['start_time'])->exists()){
            PostPlayLog::where([
                'post_play_id'=>$param->post_play_id,
            ])->update(['status' => 4]);

            Log::info("处理上传的运动数据--上传失败(重复上传):".json_encode($jsonData));
            return false;//判定异常数据，终止执行
        }

        //查询用户账号信息+我的成就信息
        $userInfo = UsrUser::with('user_achievement_one')
            ->where('user_id',$jsonData['created_uid'])
            ->first();

        //获取用户缓存
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $userInfo->access_token), true);

        //有竞标赛的情况 （只兼容四项赛）
        if (!empty($jsonData['sys_sys_match_id']) && !empty($jsonData['sys_match_id']) && $jsonData['is_quartets'] == 1){
            $matchsUserGrade = MatchsUserGrade::whereHas('matchs_stages',function ($query) use ($jsonData) {
                $query->where([
                    'user_id' => $jsonData['created_uid'],
                    'sys_match_id' => $jsonData['sys_match_id'],
                    'sys_sys_match_id' => $jsonData['sys_sys_match_id'],
                ]);
            })->first();
            $Ismatchs = 1;
            if(!$matchsUserGrade){
                Log::info('运动post_play_log:'.$param->post_play_id.' 活动不存在：'.$jsonData['created_uid'].'====='.$jsonData['sys_match_id'].'===='.$jsonData['sys_sys_match_id']);
                $Ismatchs = 0;
            }
        }

//运动数据业务处理开始
        $userPlayMap = self::userplayHandlePlayLog($jsonData,$matchsUserGrade);

//运动详情数据业务处理开始
        $userPlayDetailMap = self::userplayDetailHandlePlayLog($jsonData);

//PK、摇加油、随手摇数据上传业务逻辑到此止
        if (in_array($jsonData['source'],SettingMessage::SET_PLAYV3_TYPE)){
            return self::createPkShakeCasually($jsonData,$postPlayLog,$userPlayDetailMap,$userPlayMap);
        }

//赛事四项结果，防作弊处理,并定义异常变量
        $_abnormal_index = StaticDataController::$_abnormal_index;//防作弊规则
        //验证摇跑一分钟
        $userPlayMap['exponent_molecular'] < $_abnormal_index['exponent_molecular'] ? '' : $aberrant['exponent_molecular'] = 0;
        //验证摇跑指数
        $userPlayMap['exponent'] < $_abnormal_index['runball_exponent'] ? '' : $aberrant['exponent'] = 0;
        // $aberrant['exponent'] = 0;
        //验证摇跑一分钟内最高转速，且非异常运动记录
        $userPlayMap['exponent_speed_max'] < $_abnormal_index['exponent_speed_max'] ? '' : $aberrant['exponent_speed_max'] = 0;
        // $aberrant['exponent_speed_max'] = 0;
        //验证半马时间
        $userPlayMap['exponent_denominator'] > $_abnormal_index['exponent_denominator'] ? '' : $aberrant['exponent_denominator'] = 0;
        //验证全马时间
        $userPlayMap['marathon'] > $_abnormal_index['marathon'] ? '' : $aberrant['marathon'] = 0;

//用户成绩数据业务处理开始(返回重新$_usr_user)
        $userAchievementOrUsrUserData = self::userAchievementHandlePlayLog($jsonData, $userInfo, $_usr_user, $aberrant);

//用户竞标赛成绩数据业务处理开始
        $matchsUserGradeMap = self::matchsUserGradeHandlePlayLog($jsonData, $matchsUserGrade, $aberrant, $Ismatchs);
        
        Log::info('============userAchievementOrUsrUserData1:'.json_encode($userAchievementOrUsrUserData));

//数据写入+事务
        //查询是否有加入战队
        $userClanMember = UserClanMember::where('user_id',$jsonData['created_uid'])->first();

        //查询当前运动是否存在
        $userPlay = UserPlay::where([
                "user_play_id" => $jsonData["user_play_id"],
                "user_id" => $jsonData['created_uid'],
            ])->select("user_id")->first();

        try {
            DB::transaction(function () use ($userClanMember, $matchsUserGrade, $postPlayLog, $userInfo, $Ismatchs, $matchsUserGradeMap, $userAchievementOrUsrUserData, $jsonData, $userPlayDetailMap, $userPlayMap, $userPlay) {
                //写入运动数据 与 写入运动详情数据
                if ($userPlay){//存在，就更新
                    unset($userPlayMap['user_play_id'],$userPlayDetailMap['user_play_detail_id']);
                    $userPlaydata = UserPlay::where('user_play_id',$userPlay->user_play_id)->update($userPlayMap);
                    $userPlayDetailData = UserPlayDetail::where('user_play_id',$userPlay->user_play_id)->update($userPlayDetailMap);
                }else{
                    $userPlaydata = UserPlay::create($userPlayMap);
                    $userPlayDetailData = UserPlayDetail::create($userPlayDetailMap);
                }

                //异常记录数据，不更新近用户成绩和用户锦标赛成绩，is_abnormal: 0 正常 1异常
                if ($jsonData['is_abnormal'] == 0){
                    //写入用户成绩，突破成绩
                    if (count($userAchievementOrUsrUserData['userAchievementMap']) > 0){
                        $userAchievementData = UserAchievement::where('user_id',$jsonData['created_uid'])->update($userAchievementOrUsrUserData['userAchievementMap']);
                        //冗余战队成员成绩修改
                        if (!empty($userClanMember)){
                            isset($userAchievementOrUsrUserData['userAchievementMap']['speed_max']) ? $userClanMember->avg_speed_max = $userAchievementOrUsrUserData['userAchievementMap']['speed_max'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['exponent_molecular']) ? $userClanMember->avg_exponent_molecular = $userAchievementOrUsrUserData['userAchievementMap']['exponent_molecular'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['runball_exponent']) ? $userClanMember->avg_runball_exponent = $userAchievementOrUsrUserData['userAchievementMap']['runball_exponent'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['marathon']) ? $userClanMember->avg_marathon = $userAchievementOrUsrUserData['userAchievementMap']['marathon'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['exponent_speed_max']) ? $userClanMember->avg_exponent_speed_max = $userAchievementOrUsrUserData['userAchievementMap']['exponent_speed_max'] : '';
                            $userClanMember->save();
                        }
                    }

                    //写入竞标赛成绩，突破成绩
                    if ($Ismatchs == 1 && count($matchsUserGradeMap) > 0){
                        $matchsUserGradeData = MatchsUserGrade::where([
                            'user_id' => $jsonData['created_uid'],
                            'matchs_user_grade_id' => $matchsUserGrade['matchs_user_grade_id'],
                        ])->update($matchsUserGradeMap);
                    }
                }

                //修改队列结果
                PostPlayLog::where('post_play_id',$postPlayLog->post_play_id)->update(['status'=>2]);

                //更新用户账号缓存
                Redis::select(1);
                if (!empty($userInfo->access_token)) {
                    Redis::hset("usr_user", $userInfo->access_token, json_encode($userAchievementOrUsrUserData['usr_user']));
                }

            }, 5);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex);
            Log::info("处理上传的运动数据--队列调用,上传失败1==error:".json_encode($ex->getMessage()));
            Log::info("处理上传的运动数据--队列调用,上传失败2==运动数据:".json_encode($userPlayMap));
            Log::info("处理上传的运动数据--队列调用,上传失败3==运动详情数据:".json_encode($userPlayDetailMap));
            Log::info("处理上传的运动数据--队列调用,上传失败4==用户成绩数据:".json_encode($userAchievementOrUsrUserData['userAchievementMap']));
            Log::info("处理上传的运动数据--队列调用,上传失败5==用户竞标赛成绩数据:".json_encode($matchsUserGradeMap));

            return false;
        }

        return true;
    }
    
    /**
     * 处理上传的运动数据--队列调用_test
     * @param $param
     * User: zxw
     * Date: 2021/10/23 14:15
     * @throws BusinessException
     */
    public function handlePlayLogTest($param)
    {
        $matchsUserGrade = [];
        $aberrant = [];
        $Ismatchs = 0;//默认不是竞标赛

        //查询数据是否被处理过
        $postPlayLog = PostPlayLog::where([
            'post_play_id'=>$param->post_play_id
        ])->first();

        //不存在，直接返回false结束
        if (!$postPlayLog) return false;

        //json数据转数组
        $jsonData = json_decode($postPlayLog->json_data,true);
        
        // 补齐字段
        if (empty($jsonData['exponent_speed_max'])) {
            $jsonData['exponent_speed_max'] = 0;
        }
        

        //修补前端赛事半马数据提交缺失指数、半马距离、耗时的bug（修补条件满足：distance>2100&&exponent_denominator=0&&source=4&&is_quartets=1&&ranking_type_list=3）
        if ($jsonData['distance'] > 21000 && empty($jsonData['exponent_denominator']) && $jsonData['source'] == 4 && $jsonData['is_quartets'] == 1 && !empty($jsonData['sys_sys_match_id'])){
            $ranking_type_list = SysMatch::where('sys_match_id',$jsonData['sys_sys_match_id'])->value('ranking_type_list');
            if ($ranking_type_list == 3){
                $jsonData['distance'] =  21098;
                $jsonData['exponent_denominator'] = $jsonData['duration'] = $jsonData['duration']+1;
            }
        }

        //重新计算摇跑指数
        if (!empty($jsonData['exponent_speed_max']) && !empty($jsonData['exponent_molecular'])){
            // $jsonData["exponent"] = round($jsonData["exponent_molecular"]/($jsonData["exponent_denominator"]/60),2); // 摇跑指数计算
            $jsonData["exponent"] = round($jsonData['exponent_speed_max'] * $jsonData['exponent_molecular'] / 1000000,2); // 活力指数计算(摇跑1分钟内的最高转速x摇跑1分钟内的距离再除以1000=活力指数)
        }else{
            $jsonData["exponent"] = 0;
        }
        $jsonData['is_quartets'] = $jsonData['is_quartets'] ?? 0;
        $jsonData['sys_match_id'] = $jsonData['sys_match_id'] ?? 0;
        $jsonData['sys_sys_match_id'] = $jsonData['sys_sys_match_id'] ?? 0;

        //处理异常，重复提交问题
        if (UserPlay::where('user_id',$jsonData['created_uid'])->where('start_time',$jsonData['start_time'])->exists()){
            PostPlayLog::where([
                'post_play_id'=>$param->post_play_id,
            ])->update(['status' => 4]);

            Log::info("处理上传的运动数据--上传失败(重复上传):".json_encode($jsonData));
            return false;//判定异常数据，终止执行
        }

        //查询用户账号信息+我的成就信息
        $userInfo = UsrUser::with('user_achievement_one')
            ->where('user_id',$jsonData['created_uid'])
            ->first();

        //获取用户缓存
        Redis::select(1);
        $_usr_user = json_decode(Redis::hget("usr_user", $userInfo->access_token), true);

        //有竞标赛的情况 （只兼容四项赛）
        if (!empty($jsonData['sys_sys_match_id']) && !empty($jsonData['sys_match_id']) && $jsonData['is_quartets'] == 1){
            $matchsUserGrade = MatchsUserGrade::whereHas('matchs_stages',function ($query) use ($jsonData) {
                $query->where([
                    'user_id' => $jsonData['created_uid'],
                    'sys_match_id' => $jsonData['sys_match_id'],
                    'sys_sys_match_id' => $jsonData['sys_sys_match_id'],
                ]);
            })->first();
            $Ismatchs = 1;
            if(!$matchsUserGrade){
                Log::info('运动post_play_log:'.$param->post_play_id.' 活动不存在：'.$jsonData['created_uid'].'====='.$jsonData['sys_match_id'].'===='.$jsonData['sys_sys_match_id']);
                $Ismatchs = 0;
            }
        }

//运动数据业务处理开始
        $userPlayMap = self::userplayHandlePlayLog($jsonData,$matchsUserGrade);

//运动详情数据业务处理开始
        $userPlayDetailMap = self::userplayDetailHandlePlayLog($jsonData);

//PK、摇加油、随手摇数据上传业务逻辑到此止
        if (in_array($jsonData['source'],SettingMessage::SET_PLAYV3_TYPE)){
            return self::createPkShakeCasually($jsonData,$postPlayLog,$userPlayDetailMap,$userPlayMap);
        }

//赛事四项结果，防作弊处理,并定义异常变量
         $_abnormal_index = StaticDataController::$_abnormal_index;//防作弊规则
        //验证摇跑一分钟
        $userPlayMap['exponent_molecular'] < $_abnormal_index['exponent_molecular'] ? '' : $aberrant['exponent_molecular'] = 0;
        //验证摇跑指数
        // $userPlayMap['exponent'] < $_abnormal_index['runball_exponent'] ? '' : $aberrant['exponent'] = 0;
        $aberrant['exponent'] = 0;
        //验证摇跑一分钟内最高转速，且非异常运动记录
        $aberrant['exponent_speed_max'] = 0;
        //验证半马时间
        $userPlayMap['exponent_denominator'] > $_abnormal_index['exponent_denominator'] ? '' : $aberrant['exponent_denominator'] = 0;
        //验证全马时间
        $userPlayMap['marathon'] > $_abnormal_index['marathon'] ? '' : $aberrant['marathon'] = 0;

//用户成绩数据业务处理开始(返回重新$_usr_user)
        $userAchievementOrUsrUserData = self::userAchievementHandlePlayLog($jsonData, $userInfo, $_usr_user, $aberrant);

//用户竞标赛成绩数据业务处理开始
        $matchsUserGradeMap = self::matchsUserGradeHandlePlayLog($jsonData, $matchsUserGrade, $aberrant, $Ismatchs);
        
        Log::info('============userAchievementOrUsrUserData:'.json_encode($userAchievementOrUsrUserData));

//数据写入+事务
        //查询是否有加入战队
        $userClanMember = UserClanMember::where('user_id',$jsonData['created_uid'])->first();

        //查询当前运动是否存在
        $userPlay = UserPlay::where([
                "user_play_id" => $jsonData["user_play_id"],
                "user_id" => $jsonData['created_uid'],
            ])->select("user_id")->first();

        try {
            DB::transaction(function () use ($userClanMember, $matchsUserGrade, $postPlayLog, $userInfo, $Ismatchs, $matchsUserGradeMap, $userAchievementOrUsrUserData, $jsonData, $userPlayDetailMap, $userPlayMap, $userPlay) {
                //写入运动数据 与 写入运动详情数据
                if ($userPlay){//存在，就更新
                    unset($userPlayMap['user_play_id'],$userPlayDetailMap['user_play_detail_id']);
                    $userPlaydata = UserPlay::where('user_play_id',$userPlay->user_play_id)->update($userPlayMap);
                    $userPlayDetailData = UserPlayDetail::where('user_play_id',$userPlay->user_play_id)->update($userPlayDetailMap);
                }else{
                    $userPlaydata = UserPlay::create($userPlayMap);
                    $userPlayDetailData = UserPlayDetail::create($userPlayDetailMap);
                }

                //异常记录数据，不更新近用户成绩和用户锦标赛成绩，is_abnormal: 0 正常 1异常
                if ($jsonData['is_abnormal'] == 0){
                    //写入用户成绩，突破成绩
                    if (count($userAchievementOrUsrUserData['userAchievementMap']) > 0){
                        $userAchievementData = UserAchievement::where('user_id',$jsonData['created_uid'])->update($userAchievementOrUsrUserData['userAchievementMap']);
                        //冗余战队成员成绩修改
                        if (!empty($userClanMember)){
                            isset($userAchievementOrUsrUserData['userAchievementMap']['speed_max']) ? $userClanMember->avg_speed_max = $userAchievementOrUsrUserData['userAchievementMap']['speed_max'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['exponent_molecular']) ? $userClanMember->avg_exponent_molecular = $userAchievementOrUsrUserData['userAchievementMap']['exponent_molecular'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['runball_exponent']) ? $userClanMember->avg_runball_exponent = $userAchievementOrUsrUserData['userAchievementMap']['runball_exponent'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['marathon']) ? $userClanMember->avg_marathon = $userAchievementOrUsrUserData['userAchievementMap']['marathon'] : '';
                            isset($userAchievementOrUsrUserData['userAchievementMap']['exponent_speed_max']) ? $userClanMember->avg_exponent_speed_max = $userAchievementOrUsrUserData['userAchievementMap']['exponent_speed_max'] : '';
                            $userClanMember->save();
                        }
                    }

                    //写入竞标赛成绩，突破成绩
                    if ($Ismatchs == 1 && count($matchsUserGradeMap) > 0){
                        $matchsUserGradeData = MatchsUserGrade::where([
                            'user_id' => $jsonData['created_uid'],
                            'matchs_user_grade_id' => $matchsUserGrade['matchs_user_grade_id'],
                        ])->update($matchsUserGradeMap);
                    }
                }

                //修改队列结果
                PostPlayLog::where('post_play_id',$postPlayLog->post_play_id)->update(['status'=>2]);

                //更新用户账号缓存
                Redis::select(1);
                if (!empty($userInfo->access_token)) {
                    Redis::hset("usr_user", $userInfo->access_token, json_encode($userAchievementOrUsrUserData['usr_user']));
                }

            }, 5);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex);
            Log::info("处理上传的运动数据--队列调用,上传失败1==error:".json_encode($ex->getMessage()));
            Log::info("处理上传的运动数据--队列调用,上传失败2==运动数据:".json_encode($userPlayMap));
            Log::info("处理上传的运动数据--队列调用,上传失败3==运动详情数据:".json_encode($userPlayDetailMap));
            Log::info("处理上传的运动数据--队列调用,上传失败4==用户成绩数据:".json_encode($userAchievementOrUsrUserData['userAchievementMap']));
            Log::info("处理上传的运动数据--队列调用,上传失败5==用户竞标赛成绩数据:".json_encode($matchsUserGradeMap));

            return false;
        }

        return true;
    }

    /**
     * 处理上传的运动数据--队列调用 =====> 运动数据业务处理
     * @param $jsonData
     * @param $matchsUserGrade
     * @return array
     * User: zxw
     * Date: 2021/10/23 16:15
     */
    public static function userplayHandlePlayLog($jsonData,$matchsUserGrade): array
    {
        //查询用户最后一次正常的记录
        $userPlay = UserPlay::select('distance')
            ->where([
                'user_id' => $jsonData['created_uid'],
                'is_abnormal' => 0
            ])->whereTime('stop_time','<',$jsonData['start_time'])
            ->orderBy('stop_time','DESC')
            ->first();

        //判断本次记录与上传记录变化情况
        $compare_last = 0;
        empty($userPlay) ? $compare_last = 0 : ($jsonData['distance'] > $userPlay['distance'] ? $compare_last = 1 : $compare_last = -1);

        //组装运动数据
        $userPlayMap = [
            "user_play_id" => $jsonData["user_play_id"],
            "source" => $jsonData["source"] ?? 0,
            "status" => 1,
            "duration" => $jsonData["duration"],
            "speed_max" => $jsonData["speed_max"],
            "circle_count" => $jsonData["circle_count"],
            "endurance_max" => $jsonData["endurance_max"],
            "compare_last" => $compare_last,
            "start_time" => $jsonData["start_time"],
            "stop_time" => $jsonData["stop_time"],
            "distance" => $jsonData["distance"],
            "user_id" => $jsonData["created_uid"],
            "is_abnormal" => $jsonData["is_abnormal"],
            "exponent_molecular" => $jsonData["exponent_molecular"],//摇跑指数，分子
            "exponent_denominator" => $jsonData["exponent_denominator"],//摇跑指数，分母
            "exponent_speed_max" => $jsonData["exponent_speed_max"], //摇跑指数，1分钟内最高转速
            "exponent" => $jsonData["exponent"],//摇跑指数
            "marathon" => $jsonData["marathon"],//摇跑马拉松（全马）
            "created_time" => $jsonData["stop_time"],
            "updated_time" => $jsonData["stop_time"],
        ];
        if (isset($jsonData["user_pk_list_id"])) {//补充$userPlayMap数据
            $userPlayMap["user_pk_list_id"] = $jsonData["user_pk_list_id"];
        }
        if (isset($matchsUserGrade['matchs_stage_id'])) {//补充$userPlayMap数据
            $userPlayMap["matchs_stage_id"] = $matchsUserGrade['matchs_stage_id'];
        }
        if (isset($jsonData["sys_shake_id"])) {//补充$userPlayMap数据
            $userPlayMap["sys_shake_id"] = $jsonData["sys_shake_id"];
        }

        return $userPlayMap;
    }

    /**
     * 处理上传的运动数据--队列调用 =====> 运动详情数据业务处理
     * @param $jsonData
     * @return array
     * User: zxw
     * Date: 2021/10/23 16:20
     */
    public static function userplayDetailHandlePlayLog($jsonData): array
    {
        $_arr_section = [];

        //获取区间数组
        $_speed_section = StaticDataController::$_speed_section;
        for ($_i=0;$_i<count($_speed_section);$_i++){
            if($_i>0){
                $_key = $_speed_section[$_i-1]."-".$_speed_section[$_i];
                $_arr_section[$_key] = array(
                    "start_section"=>$_speed_section[$_i-1],
                    "stop_section"=>$_speed_section[$_i],
                    "speed_detail"=>array(),
                    "section_duration"=>0
                );
            }
        }

        //转速时刻
        $_user_play_detail = array();
        //循环圈数数组，
        for ($_i=0;$_i<count($jsonData["speed_detail"]);$_i++){
            //当前速度 rpm  （当前圈数-上一秒圈数）*60秒*(1000 / 时间间隔 毫秒)，
            $_speed = $jsonData["speed_detail"][$_i];
            $_moment = $jsonData["start_time"]*1000+$jsonData["interval"]*$_i;
            array_push($_user_play_detail,array(
                "moment"=>$_moment,
                "speed"=>$_speed,
            ));
            foreach ($_arr_section as $key=>$node){
                if($_speed>=$node["start_section"]&&$_speed<$node["stop_section"]){
                    array_push($node["speed_detail"],$_speed);
                }
                $_arr_section[$key] = $node;
            }
        }
        $_max_section_duration = 0;
        foreach ($_arr_section as $key=>$value){
            $value["section_duration"] = round(count($value["speed_detail"])*$jsonData["interval"]/1000);
            unset($value["speed_detail"]);
            $_arr_section[$key] = $value;
            if($_max_section_duration<$value["section_duration"]){
                $_max_section_duration = $value["section_duration"];
            }
        }
        foreach ($_arr_section as $key=>$value){
            $_percentage = $_max_section_duration>0?round($value["section_duration"]/$_max_section_duration*100):$_max_section_duration;
            $value["percentage"] = $_percentage;
            $_arr_section[$key] = $value;
        }

        //组装运动详情数据
        return [
            "status" => 1,
            "user_play_detail_id" => $jsonData["user_play_detail_id"],
            "speed_interval" => $jsonData["interval"],
            "user_play_id" => $jsonData["user_play_id"],
            "section_duration" => json_encode($_arr_section),
            "speed_detail" => json_encode($_user_play_detail),
            "created_time" => $jsonData["stop_time"],
            "updated_time" => $jsonData["stop_time"],
        ];
    }

    /**
     * 处理上传的运动数据--队列调用 =====> 用户成绩数据业务处理
     * @param $jsonData
     * @param $userInfo
     * @param $_usr_user
     * @param $aberrant
     * @return array
     * User: zxw
     * Date: 2021/10/23 18:15
     */
    public static function userAchievementHandlePlayLog($jsonData, $userInfo, $_usr_user, $aberrant): array
    {
        // Ensure all exponent fields exist (PHP 8.2 strict mode compatibility)
        $jsonData['exponent_speed_max'] = $jsonData['exponent_speed_max'] ?? 0;
        $jsonData['exponent_molecular'] = $jsonData['exponent_molecular'] ?? 0;
        $jsonData['exponent_denominator'] = $jsonData['exponent_denominator'] ?? 0;
        $jsonData['exponent'] = $jsonData['exponent'] ?? 0;
        $jsonData['stop_time'] = $jsonData['stop_time'] ?? 0;
        
        $userAchievementMap = [];
        //不需要过滤的突破成绩更新
        //1、持续时间记录
        if ($jsonData['duration'] > $userInfo->user_achievement_one->duration){
            $_usr_user['achievement']['duration'] = $userAchievementMap['duration'] = $jsonData['duration'];
        }
        //2、最高转速记录
        if ($jsonData['speed_max'] > $userInfo->user_achievement_one->speed_max){
            $_usr_user['achievement']['speed_max'] = $userAchievementMap['speed_max'] = $jsonData['speed_max'];
            $userAchievementMap['speed_max_time'] = $jsonData["stop_time"];
        }
        //3、最高圈数记录
        if ($jsonData['circle_count'] > $userInfo->user_achievement_one->circle_count){
            $_usr_user['achievement']['circle_count'] = $userAchievementMap['circle_count'] = $jsonData['circle_count'];
        }
        //4、耐力记录
        if ($jsonData['endurance_max'] > $userInfo->user_achievement_one->endurance_max){
            $_usr_user['achievement']['endurance_max'] = $userAchievementMap['endurance_max'] = $jsonData['endurance_max'];
        }
        //5、累计运动次数
        $_usr_user['achievement']['play_count'] = $userAchievementMap['play_count'] = $userInfo->user_achievement_one->play_count + 1;

        //如果没有异常，且有突破成绩就更新
        //突破:摇跑一分钟，且非异常运动记录
        if (!isset($aberrant['exponent_molecular'])){
            if ($jsonData['exponent_molecular'] > $userInfo->user_achievement_one->exponent_molecular){
                $_usr_user['achievement']['exponent_molecular'] = $userAchievementMap['exponent_molecular'] = $jsonData['exponent_molecular'];
                $userAchievementMap['exponent_molecular_time'] = $jsonData["stop_time"];
            }
        }
        
        //突破:摇跑一分钟内最高转速，且非异常运动记录
        // if (!isset($aberrant['exponent_speed_max'])){
        if ($jsonData['exponent_speed_max'] > $userInfo->user_achievement_one->exponent_speed_max){
            $_usr_user['achievement']['exponent_speed_max'] = $userAchievementMap['exponent_speed_max'] = $jsonData['exponent_speed_max'];
            $userAchievementMap['exponent_speed_max_time'] = $jsonData["stop_time"];
        }
        Log::info("====exponent_speed_max:".($_usr_user['achievement']['exponent_speed_max'] ?? 0).'=====exponent_speed_max_time:'.($userAchievementMap['exponent_speed_max_time'] ?? 0));
        // }
        
        Log::info('====1111111=====');
        //突破:摇跑指数，且非异常运动记录
        // if (!isset($aberrant['exponent'])){
        if ($jsonData['exponent'] > $userInfo->user_achievement_one->runball_exponent){
            $_usr_user['achievement']['runball_exponent'] = $userAchievementMap['runball_exponent'] = $jsonData['exponent'];
            $userAchievementMap['runball_exponent_time'] = $jsonData["stop_time"];
        }
        Log::info("====runball_exponent:".($_usr_user['achievement']['runball_exponent'] ?? 0).'=====runball_exponent_time:'.($userAchievementMap['runball_exponent_time'] ?? 0));
        // }
        
        //突破:半马时间，且非异常运动记录(完成21.098km花的时间)===值越小越优秀
        if (!isset($aberrant['exponent_denominator'])){
            if (!empty($userInfo->user_achievement_one->exponent_denominator)){
                if ($jsonData['exponent_denominator'] < $userInfo->user_achievement_one->exponent_denominator){
                    $_usr_user['achievement']['exponent_denominator'] = $userAchievementMap['exponent_denominator'] = $jsonData['exponent_denominator'];
                }
            }else{
                $_usr_user['achievement']['exponent_denominator'] = $userAchievementMap['exponent_denominator'] = $jsonData['exponent_denominator'];
            }
        }
        //突破:全马时间，且非异常运动记录(完成21.098km花的时间)===值越小越优秀
        if (!isset($aberrant['marathon'])){
            if (!empty($userInfo->user_achievement_one->marathon)){
                if ($jsonData['marathon'] < $userInfo->user_achievement_one->marathon){
                    $_usr_user['achievement']['marathon'] = $userAchievementMap['marathon'] = $jsonData['marathon'];
                    $userAchievementMap['marathon_time'] = $jsonData["stop_time"];
                }
            }else{
                $_usr_user['achievement']['marathon'] = $userAchievementMap['marathon'] = $jsonData['marathon'];
                $userAchievementMap['marathon_time'] = $jsonData["stop_time"];
            }
        }

        return [
            'usr_user' => $_usr_user,//更新缓存
            'userAchievementMap' => $userAchievementMap,
        ];
    }

    /**
     * 处理上传的运动数据--队列调用 =====> 用户竞标赛成绩数据业务处理
     * @param $jsonData
     * @param $matchsUserGrade
     * @param $aberrant
     * @param $Ismatchs
     * @return array
     * User: zxw
     * Date: 2021/10/23 18:23
     */
    public static function matchsUserGradeHandlePlayLog($jsonData, $matchsUserGrade, $aberrant, $Ismatchs): array
    {
        // Ensure all exponent fields exist (PHP 8.2 strict mode compatibility)
        $jsonData['exponent_speed_max'] = $jsonData['exponent_speed_max'] ?? 0;
        $jsonData['exponent_molecular'] = $jsonData['exponent_molecular'] ?? 0;
        $jsonData['exponent_denominator'] = $jsonData['exponent_denominator'] ?? 0;
        $jsonData['exponent'] = $jsonData['exponent'] ?? 0;
        $jsonData['stop_time'] = $jsonData['stop_time'] ?? 0;
        $matchsUserGradeMap = [];
        if ($Ismatchs == 1){
            //1、持续时间记录
            if ($jsonData['duration'] > $matchsUserGrade['s_duration']){
                $matchsUserGradeMap['s_duration'] = $jsonData['duration'];
            }
            //2、最高转速记录
            if ($jsonData['speed_max'] > $matchsUserGrade['s_speed_max']){
                $matchsUserGradeMap['s_speed_max'] = $jsonData['speed_max'];
                $matchsUserGradeMap['s_speed_max_time'] = $jsonData["stop_time"];
            }
            //3、最高圈数记录
            if ($jsonData['circle_count'] > $matchsUserGrade['s_circle_count']){
                $matchsUserGradeMap['s_circle_count'] = $jsonData['circle_count'];
            }
            //4、耐力记录
            if ($jsonData['endurance_max'] > $matchsUserGrade['s_endurance_max']){
                $matchsUserGradeMap['s_endurance_max'] = $jsonData['endurance_max'];
            }

        //如果没有异常，且有突破成绩就更新
            if (!isset($aberrant['exponent_molecular'])){
                //突破:摇跑一分钟，且非异常运动记录
                if ($jsonData['exponent_molecular'] > $matchsUserGrade['s_exponent_molecular']){
                    $matchsUserGradeMap['s_exponent_molecular'] = $jsonData['exponent_molecular'];
                    $matchsUserGradeMap['s_exponent_molecular_time'] = $jsonData["stop_time"];
                }
            }
            
            Log::info('====222222=====');
            //突破:摇跑一分钟内最高转速，且非异常运动记录
            // if (!isset($aberrant['exponent_speed_max'])){
            if ($jsonData['exponent_speed_max'] > $matchsUserGrade['s_exponent_speed_max']){
                $matchsUserGradeMap['s_exponent_speed_max'] = $jsonData['exponent_speed_max'];
                $matchsUserGradeMap['s_exponent_speed_max_time'] = $jsonData["stop_time"];
            }
            Log::info('s_exponent_speed_max==:'.$matchsUserGradeMap['s_exponent_speed_max'].'===s_exponent_speed_max_time=:'.$matchsUserGradeMap['s_exponent_speed_max_time']);
            // }
            
            //突破:摇跑指数，且非异常运动记录
            // if (!isset($aberrant['exponent'])){
            if ($jsonData['exponent'] > $matchsUserGrade['s_runball_exponent']){
                $matchsUserGradeMap['s_runball_exponent'] = $jsonData['exponent'];
                $matchsUserGradeMap['s_runball_exponent_time'] = $jsonData["stop_time"];
            }
            Log::info('s_runball_exponent==:'.$matchsUserGradeMap['s_runball_exponent'].'===s_runball_exponent_time=:'.$matchsUserGradeMap['s_runball_exponent_time']);
            // }
            
            //突破:半马时间，且非异常运动记录(完成21.098km花的时间)===值越小越优秀
            if (!isset($aberrant['exponent_denominator'])){
                if (!empty($matchsUserGrade['s_exponent_denominator'])){
                    if ($jsonData['exponent_denominator'] < $matchsUserGrade['s_exponent_denominator']){
                        $matchsUserGradeMap['s_exponent_denominator'] = $jsonData['exponent_denominator'];
                    }
                }else{
                    $matchsUserGradeMap['s_exponent_denominator'] = $jsonData['exponent_denominator'];
                }
            }
            //突破:全马时间，且非异常运动记录(完成21.098km花的时间)===值越小越优秀
            if (!isset($aberrant['marathon'])){
                if (!empty($matchsUserGrade['s_marathon'])){
                    if ($jsonData['marathon'] < $matchsUserGrade['s_marathon']){
                        $matchsUserGradeMap['s_marathon'] = $jsonData['marathon'];
                        $matchsUserGradeMap['s_marathon_time'] = $jsonData["stop_time"];
                    }
                }else{
                    $matchsUserGradeMap['s_marathon'] = $jsonData['marathon'];
                    $matchsUserGradeMap['s_marathon_time'] = $jsonData["stop_time"];
                }
            }
        }
        return $matchsUserGradeMap;
    }

    /**
     * PK、摇加油、随手摇数据上传业务逻辑到此止
     * @param $jsonData
     * @param $postPlayLog
     * @param $userPlayDetailMap
     * @param $userPlayMap
     * @return bool
     * @throws BusinessException
     */
    public static function createPkShakeCasually($jsonData,$postPlayLog,$userPlayDetailMap,$userPlayMap): bool
    {
        //查询当前运动是否存在
        $userPlay = UserPlay::where([
            "user_play_id" => $jsonData["user_play_id"],
            "user_id" => $jsonData['created_uid'],
        ])->select("user_id")->first();

        //修复摇加油前端统计不对称bug，前端差多少之间补差多少
        /*if ($jsonData['source'] == 3){
            //获取今天摇加油的sys_shake_id
            $sysShakeId = SysShake::where('datetime',strtotime(date('Y-m-d')))->value('sys_shake_id');
            if ($sysShakeId !== null){
                Redis::select(14);
                $redisUserInfoName = 'SHAKEINFO-' . $sysShakeId . '-USERINFO';
                if (Redis::hExists($redisUserInfoName, $jsonData['created_uid'])) {
                    $userInfo = Redis::hget($redisUserInfoName,$jsonData['created_uid']);
                    $userInfo = $userInfo ? json_decode($userInfo, true) : [];
                    //统计用户当天再运动记录中的距离总和
                    $countUserPlayDistance = UserPlay::where('user_id',$jsonData['created_uid'])
                        ->where('created_time','>=',strtotime(date('Y-m-d')))
                        ->where('created_time','<=',strtotime(date('Y-m-d 23:59:59')))
                        ->where('source',3)
                        ->sum('distance');
                    $duration = $userInfo['distance'] - ($countUserPlayDistance + $userPlayMap['distance']);
                    if ($userInfo['distance'] > ($countUserPlayDistance + $userPlayMap['distance'])){//少补，多减
                        $userPlayMap['distance'] = $userPlayMap['distance'] + $duration;
                        Log::info('用户：'.$jsonData['created_uid'].'摇加油距离补差:'.$userPlayMap['distance']);
                    }
                }
            }
        }*/

        try {
            DB::transaction(function () use ($postPlayLog, $jsonData, $userPlayDetailMap, $userPlayMap, $userPlay) {
                //写入运动数据 与 写入运动详情数据
                if ($userPlay){//存在，就更新
                    unset($userPlayMap['user_play_id'],$userPlayDetailMap['user_play_detail_id']);
                    $userPlaydata = UserPlay::where('user_play_id',$userPlay->user_play_id)->update($userPlayMap);
                    $userPlayDetailData = UserPlayDetail::where('user_play_id',$userPlay->user_play_id)->update($userPlayDetailMap);
                }else{
                    $userPlaydata = UserPlay::create($userPlayMap);
                    $userPlayDetailData = UserPlayDetail::create($userPlayDetailMap);
                }

                if ($jsonData['source'] == 2){//PK业务处理
                    UserPkList::where([
                        "user_pk_list_id" => $userPlayMap['user_pk_list_id'],
                        "user_id" => $userPlayMap["user_id"],
                    ])->update([
                        "duration" => $userPlayMap["duration"],
                        "distance" => $userPlayMap["distance"],
                        "circle_count" => $userPlayMap["circle_count"],
                    ]);
                }

                //修改队列结果
                PostPlayLog::where('post_play_id',$postPlayLog->post_play_id)->update(['status'=>2]);

            }, 5);
        }catch (\Throwable $ex){
            throw new BusinessException(ErrorCode::SEVER_ERROR, $ex);
            Log::info("处理上传的运动数据--队列调用,上传失败1==error:".json_encode($ex->getMessage()));
            Log::info("处理上传的运动数据--队列调用,上传失败2==运动数据:".json_encode($userPlayMap));
            Log::info("处理上传的运动数据--队列调用,上传失败3==运动详情数据:".json_encode($userPlayDetailMap));
            return false;
        }
        return true;
    }

}
