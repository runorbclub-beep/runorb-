<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\BaseFunc\Upload;
use App\Models\MatchsUser;
use App\Models\MatchsUserGrade;
use App\Models\UserAchievement;
use App\Models\UsrUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    //
    public function test()
    {
        $_arrOfUserData = UsrUser::where([
            "status" => 1
        ])->select("*")->skip(0)->take(10)->get();

        return array(
            "code" => 1,
            "msg" => "success",
            "data" => $_arrOfUserData
        );

    }

    /**
     * 删除竞标赛报名
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function delMatchsUserGrade(Request $request): JsonResponse
    {
        $data = $request->all();
        if (!isset($data['phone']) || empty($data['phone'])) throw new BusinessException(0,'1参数错误!');
        if (!isset($data['sys_match_id']) || empty($data['sys_match_id'])) throw new BusinessException(0,'2参数错误!');
        if (empty($data['type'])) throw new BusinessException(0,'3参数错误!');

        $usrUser = UsrUser::where('phone',$data['phone'])->first();
        $matchsUser = MatchsUser::where(['sys_match_id' => $data['sys_match_id'],'user_id' => $usrUser['user_id']])->first();
        $matchsUserGrade = MatchsUserGrade::where('matchs_user_id',$matchsUser['matchs_user_id'])->first();
        $userAchievement = UserAchievement::where('user_id',$usrUser['user_id'])->first();

        $map = [
            'duration' => 0,
            'speed_max' => 0,
            'circle_count' => 0,
            'endurance_max' => 0,
            'play_count' => 0,
            'distance_max' => 0,
            'thrmin' => 0,
            'half_marathon' => 0,
            'marathon' => 0,
            'exponent_denominator' => 0,
            'exponent_molecular' => 0,
            'runball_exponent' => 0,
            'speed_max_time' => 0,
            'runball_exponent_time' => 0,
            'exponent_molecular_time' => 0,
            'marathon_time' => 0,
        ];

        try {
            DB::transaction(function () use ($map, $data,$usrUser,$matchsUser,$matchsUserGrade,$userAchievement){
                if (!empty($matchsUserGrade)){
                    $matchsUserGrade->where('matchs_user_id',$matchsUser['matchs_user_id'])->delete();
                }
                if (!empty($matchsUser)){
                    $matchsUser->where(['sys_match_id' => $data['sys_match_id'],'user_id' => $usrUser['user_id']])->delete();
                }
                if ($data['type']==2){
                    $userAchievement->where('user_id',$usrUser['user_id'])->update($map);
                }
            }, 5);
        }catch (\Throwable $ex){
            throw new BusinessException(0,$ex);
        }
        return $this->success([],'删除成功',1);
    }
}
