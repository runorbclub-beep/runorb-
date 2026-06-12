<?php

namespace App\Exports\UserPlay;

use App\Models\QiyeShakeUser;
use App\Models\UsrUser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class UserAchievementExport implements FromView
{
    protected $returnData;

    public function view(): View
    {
        $this->returnData = self::getUserAchievementExport();

        return view('web.user_play.user_achievement', [
            'user_achievement' => $this->returnData,
        ]);
    }

    /**
     * 业务处理
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     * User: zxw
     * Date: 2021/11/24 16:56
     */
    public function getUserAchievementExport()
    {
        $arr = [13751129822, 13528729426, 13790778543, 13686898776, 13410508458, 13189721693, 15811817809, 18276582665, 15015936465, 13534227720, 13415808226, 13554991271, 18677636412, 13434421660, 15575443017, 13823511502, 15329841009, 18327567357, 17688537570, 13714569504, 13809803067, 16620845166, 13480105618, 18182070019, 13535387224, 13925728236, 13755443485, 16675585136, 18312103941, 13242905040, 13923709156, 17665314755, 18926763985, 15814082116, 15217839446, 15922883375, 15099910372, 15112692950, 15332200951, 15914060511, 15986770693, 13469522740, 13760269883, 15820355779, 13428694935, 18617141036, 13417556748, 18377003287, 15049085521, 18676739603, 13480943415, 15813842855, 13651468965, 18575506133, 17875424001, 15989311550, 18320810454, 13138382920, 15818763660, 13418601731, 18808101721, 15112340715, 13798287343];

        return UsrUser::with('user_achievement_one:user_id,runball_exponent', 'qiye_shake_user:user_id,name,sex')
            ->select('user_id', 'phone')
            ->whereIn('phone', $arr)
            ->get()
            ->map(function ($items) {
                if (empty($items->user_achievement_one)) {
                    $items->runball_exponent = '无';
                } else {
                    $items->runball_exponent = $items->user_achievement_one->runball_exponent;
                }
                if (empty($items->qiye_shake_user)) {
                    $items->name = '无';
                    $items->sex = '无';
                } else {
                    $items->name = $items->qiye_shake_user->name;
                    $items->sex = $items->qiye_shake_user->sex;
                }
                unset($items->user_achievement_one, $items->qiye_shake_user);
                return $items;
            });
    }
}
