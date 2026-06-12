
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


//Route::post("/test", [\App\Http\Controllers\TestController::class, "test"]);

Route::any('/test',[\App\Http\Controllers\Api\testController::class,"test"]);
Route::any('/test2',[\App\Http\Controllers\Api\testController::class,"test2"]);

//手动创建摇加油比赛
Route::get('/createShake',[\App\Http\Controllers\Api\Shake\TestShakeController::class,"createShake"]);

Route::post('/app/font',[\App\Http\Controllers\Api\SystemController::class,"postAppFont"]);
//APP启动广告图
Route::post('/app/advertising',[\App\Http\Controllers\Api\SystemController::class,"postAppAdvertising"]);


//前端获取随机用户信息
Route::post('/rand/user',[\App\Http\Controllers\Api\UserController::class,"postRandUser"]);

//获取国家地区区号
Route::post('/overseas/code',[\App\Http\Controllers\Api\UserController::class,"postOverseasCode"]);

//助通科技短信群发送接口
Route::any('/sent/sendSmsPa',[\App\Http\Controllers\Api\PhoneController::class,"sendSmsPa"]);
//发送短信
Route::post('/sent/msg',[\App\Http\Controllers\Api\PhoneController::class,"bindPhone"]);
Route::post('/sent/email',[\App\Http\Controllers\Api\EmailController::class,"bindEmail"]);

//手机号登陆
Route::post('/login/phone',[\App\Http\Controllers\Api\UserController::class,"postLoginPhone"]);
//邮箱登陆
Route::post('/login/email',[\App\Http\Controllers\Api\UserController::class,"postLoginEmail"]);
//三方登录
Route::post('/login/tripartite',[\App\Http\Controllers\Api\UserController::class,"tripartite"]);


//运动作弊信息
Route::post('/play/cheat/data',[\App\Http\Controllers\Api\UserPlayController::class,"postPlayCheatData"]);


//Android  APP新版本检测
Route::post('/android/version/check',[\App\Http\Controllers\Api\SystemController::class,"postAndroidVersionCheck"]);

//需要验证token的路由
//    内部PK结束接口（WS服务器调用，无需token）
Route::post('/pk/internal/stop',[\App\Http\Controllers\Api\PkController::class,"internalPkStop"]);

Route::middleware(['api_certification'])->group(function () {

//用户基本信息修改
    Route::post('/user/change',[\App\Http\Controllers\Api\UserController::class,"postUserChange"]);
//用户微信授权
    Route::post('/login/open/weixin',[\App\Http\Controllers\Api\UserController::class,"postLoginOpenWx"]);
//用户基本信息修改
    Route::post('/user/header/img/upload',[\App\Http\Controllers\Api\UserController::class,"postUserHeaderImgUpload"]);
//获取用户信息
    Route::post('/user/info',[\App\Http\Controllers\Api\UserController::class,"postUserInfo"]);
//我的徽章
    Route::post('/my/medal',[\App\Http\Controllers\Api\UserController::class,"postMyMedal"]);
//更新用户设备与系统信息
    Route::post('/user/system/change',[\App\Http\Controllers\Api\UserController::class,"postUserDeviceChange"]);

//清除用户历史记录数据
    Route::post('/user/achievement/delete',[\App\Http\Controllers\Api\UserController::class,"postUserAchievementDelete"]);
//清除用户徽章数据
    Route::post('/user/medal/delete',[\App\Http\Controllers\Api\UserController::class,"postUserMedalDelete"]);
//绑定手机号
    Route::post('/my/bind/phone',[\App\Http\Controllers\Api\UserController::class,"postBindPhone"]);
//获取用户摇跑指数
    Route::post('/runball/exponent',[\App\Http\Controllers\Api\UserController::class,"postRunballExponent"]);
//获取用户摇跑指数  更新版
    Route::post('/runball/exponent/v2',[\App\Http\Controllers\Api\UserController::class,"postRunballExponentV2"]);
//获取用户摇跑指数  更新版
    Route::post('/runball/exponent/v3',[\App\Http\Controllers\Api\UserController::class,"postRunballExponentV3"]);
//    前端上传用户最新摇跑指数
    Route::post('/runball/exponent/add',[\App\Http\Controllers\Api\UserController::class,"postRunballExponentAdd"]);


//我的成就
    Route::post('/my/achievement',[\App\Http\Controllers\Api\UserAchievementController::class,"postMyAchievement"]);
//我的运动数据
    Route::post('/my/play/data',[\App\Http\Controllers\Api\UserPlayController::class,"postMyPlayData"]);
//我的运动数据
    Route::post('/v2/my/play/data',[\App\Http\Controllers\Api\UserPlayController::class,"postMyPlayDataV2"]);
//我的记录-摇跑模式统计
    Route::post('/my/play/statistics',[\App\Http\Controllers\Api\UserPlayController::class,"postMyPlayStatistics"]);


//创建运动
    Route::post('/start/play',[\App\Http\Controllers\Api\UserPlayController::class,"postStartPlay"]);
//运动过程中实时传输数据
    Route::post('/between/play',[\App\Http\Controllers\Api\UserPlayController::class,"postBetweenPlay"]);

//结束运动
    Route::post('/stop/play',[\App\Http\Controllers\Api\UserPlayController::class,"postStopPlay"]);
//用户三分钟运动圈数
    Route::post('/play/thrmin',[\App\Http\Controllers\Api\UserPlayController::class,"postPlayThrmin"]);
//用户运动半马距离用时
    Route::post('/play/half/marathon',[\App\Http\Controllers\Api\UserPlayController::class,"postPlayHalfMarathon"]);
//运动数据监测到异常
    Route::post('/play/abnormal',[\App\Http\Controllers\Api\UserPlayController::class,"postUserPlayAbnormal"]);


//摇跑指数，分子
    Route::post('/play/exponent/molecular',[\App\Http\Controllers\Api\UserPlayController::class,"postExponentMolecular"]);
//摇跑指数，分母
    Route::post('/play/exponent/denominator',[\App\Http\Controllers\Api\UserPlayController::class,"postExponentDenominator"]);


//用户运动全马拉松距离用时
    Route::post('/play/marathon',[\App\Http\Controllers\Api\UserPlayController::class,"postPlayMarathon"]);
//结束运动
    Route::post('/play/info',[\App\Http\Controllers\Api\UserPlayController::class,"postPlayInfo"]);
//获取运动时间范围(废弃：截止版本iOS1.2.3  安卓1.2.0.6)
    Route::post('/play/date/range',[\App\Http\Controllers\Api\UserPlayController::class,"postPlayDateRange"]);


//查询我的设备列表
    Route::post('/my/device',[\App\Http\Controllers\Api\DeviceController::class,"postMyDevice"]);
//新增设备
    Route::post('/my/device/add',[\App\Http\Controllers\Api\DeviceController::class,"postMyDeviceAdd"]);
//新增设备
    Route::post('/my/device/delete',[\App\Http\Controllers\Api\DeviceController::class,"postMyDeviceDel"]);
//新增设备
    Route::post('/my/device/update',[\App\Http\Controllers\Api\DeviceController::class,"postMyDeviceUpdate"]);



//系统比赛项目
    Route::post('/match/event/list',[\App\Http\Controllers\Api\MatchController::class,"matchEventList"]);
//赛事列表
    Route::post('/match/list',[\App\Http\Controllers\Api\MatchController::class,"matchList"]);

//赛事详情
    Route::post('/match/info',[\App\Http\Controllers\Api\MatchController::class,"matchInfo"]);
//用户根据团队编号查询
    Route::post('/match/user/group',[\App\Http\Controllers\Api\MatchController::class,"postUserGroupInfo"]);
//用户报名
    Route::post('/match/user/sign',[\App\Http\Controllers\Api\MatchController::class,"matchUserSign"]);
//用户比赛前查询
    Route::post('/match/befor/play',[\App\Http\Controllers\Api\MatchController::class,"beforMatchPlayStart"]);
//用户取消报名
    Route::post('/match/user/sign/out',[\App\Http\Controllers\Api\MatchController::class,"matchUserSignOut"]);
//用户关注列表
    Route::post('/match/user/like',[\App\Http\Controllers\Api\MatchController::class,"matchUserLike"]);
//宣传图
    Route::post('/match/banner/list',[\App\Http\Controllers\Api\MatchController::class,"matchBannerList"]);


//    获取、刷新房间号
    Route::post('/pk/room/number',[\App\Http\Controllers\Api\PkController::class,"pkRoomNumber"]);
//    创建PK房间
    Route::post('/pk/create/room',[\App\Http\Controllers\Api\PkController::class,"pkCreatedRoom"]);
//    PK房间列表
    Route::post('/pk/room/list',[\App\Http\Controllers\Api\PkController::class,"pkRoomList"]);
//    用户报名加入PK房间
    Route::post('/pk/join/room',[\App\Http\Controllers\Api\PkController::class,"pkJoinRoom"]);
//    用户进入房间查询详情
    Route::post('/pk/room/info',[\App\Http\Controllers\Api\PkController::class,"pkRoomInfo"]);
//    用户切换队伍
    Route::post('/pk/change/group',[\App\Http\Controllers\Api\PkController::class,"pkChangeGroup"]);
//    用户取消PK
    Route::post('/pk/user/pk/list/delete',[\App\Http\Controllers\Api\PkController::class,"userPkListDelete"]);
//    用户点击开始PK
    Route::post('/pk/user/pk/start',[\App\Http\Controllers\Api\PkController::class,"userPkStart"]);
//    用户完成PK
    Route::post('/pk/user/pk/stop',[\App\Http\Controllers\Api\PkController::class,"userPkStop"]);


//    我的赛事
    Route::post('/my/match',[\App\Http\Controllers\Api\MyController::class,"myMatch"]);
//    赛事赛段
    Route::post('/my/match/stage',[\App\Http\Controllers\Api\MyController::class,"myMatchStage"]);
//    赛事赛段
    Route::post('/my/match/info',[\App\Http\Controllers\Api\MyController::class,"myMatchInfo"]);


//    我的PK
    Route::post('/my/pk/list',[\App\Http\Controllers\Api\MyController::class,"myPkList"]);
//    我的PK列表==v2
    Route::post('/v2/my/pk/list',[\App\Http\Controllers\Api\MyController::class,"myPkListV2"]);
//    PK 数据详情
    Route::post('/my/pk/list/info',[\App\Http\Controllers\Api\MyController::class,"myPkListInfo"]);


//    app 榜单列表
    Route::post('/ranking/list',[\App\Http\Controllers\Api\RankingController::class,"postRankingTypeList"]);
//    app 榜单
    Route::post('/my/ranking',[\App\Http\Controllers\Api\RankingController::class,"postRunballRanking"]);
//    app 榜单
    Route::post('/my/ranking/v2',[\App\Http\Controllers\Api\RankingController::class,"postRunballRankingV2"]);
    //    app 今日最高转速 榜单
    Route::post('/my/ranking/todayHighestSpeed',[\App\Http\Controllers\Api\RankingController::class,"postRankingTodayHighestSpeed"]);
    //    app 今日累计距离 榜单
    Route::post('/my/ranking/accumulatedDistanceToday',[\App\Http\Controllers\Api\RankingController::class,"postRankingAccumulatedDistanceToday"]);

    //APP自定义榜单--添加
    Route::post('/my/ranking/add',[\App\Http\Controllers\Api\UserRankListController::class,"postMyRankingAdd"]);
    //APP自定义榜单--列表
    Route::post('/my/ranking/list',[\App\Http\Controllers\Api\UserRankListController::class,"postMyRankingList"]);
    //APP自定义榜单--删除
    Route::post('/my/ranking/del',[\App\Http\Controllers\Api\UserRankListController::class,"postMyRankingDel"]);

//我的打榜详情
    Route::post('/my/rankingDetails',[\App\Http\Controllers\Api\UserRankListController::class,"myRankingDetails"]);
//他人的打榜详情
    Route::post('/my/othersRankingDetails',[\App\Http\Controllers\Api\UserRankListController::class,"othersRankingDetails"]);



//    用户报名成为会员查询基本数据
    Route::post('/before/members',[\App\Http\Controllers\Api\Members\MembersController::class,"postBeforeMembers"]);
//    用户申请成为会员
    Route::post('/members/add',[\App\Http\Controllers\Api\Members\MembersController::class,"postMembersAdd"]);


    /*************************************************** 摇加油 **********************************************************/
    Route::post('/shake/area',[\App\Http\Controllers\Api\Shake\ShakeController::class,"postAreaList"]);      //获取地区
    Route::post('/shake/index',[\App\Http\Controllers\Api\Shake\ShakeController::class,"postshakeIndex"]);   //首页
    Route::post('/shake/info',[\App\Http\Controllers\Api\Shake\ShakeController::class,"postShakeInfo"]);     //获取赛事详情
    Route::post('/shake/sign',[\App\Http\Controllers\Api\Shake\ShakeController::class,"postShakeSign"]);     //报名
    Route::post('/shake/rule',[\App\Http\Controllers\Api\Shake\ShakeController::class,"postShakeRule"]);     //规则
    Route::post('/my/shake/list',[\App\Http\Controllers\Api\Shake\ShakeController::class,"myShakeList"]);    //我的记录
    Route::post('/my/shake/info',[\App\Http\Controllers\Api\Shake\ShakeController::class,"myShakeInfo"]);    //我的记录详情
    Route::post('/my/shake/getMyShakeBoostRanking',[\App\Http\Controllers\Api\Shake\ShakeController::class,"getMyShakeBoostRanking"]);    //获取助力排行
    Route::post('/my/shake/getMyShakeHelpDetail',[\App\Http\Controllers\Api\Shake\ShakeController::class,"getMyShakeHelpDetail"]);    //获取助力排行详情


//    本地运动数据上传
    Route::post('/local/play/upload',[\App\Http\Controllers\Api\LocalPlay\LocalPlayController::class,"postUploadLocalPlay"]);




/*********************************************** 全云动API-赛事改版 *************************************************/
////根据状态获取赛事列表
//Route::post('/v2/match/getMatchList',[App\Http\Controllers\Api\MatchV2\MatchV2Controller::class,'getMatchList']);
////根据赛事ID获取赛事详情
//Route::post('/v2/match/getSysMatchDetails',[App\Http\Controllers\Api\MatchV2\MatchV2Controller::class,'getSysMatchDetails']);


//赛事列表v2==新版赛事
    Route::post('/v2/match/list',[\App\Http\Controllers\Api\MatchController::class,"matchListV2"]);
//赛事详情v2==新版赛事
    Route::post('/v2/match/info',[\App\Http\Controllers\Api\MatchController::class,"matchInfoV2"]);
//获取赛段截止时间v2==新版赛事
    Route::post('/v2/match/getStageStopTime',[\App\Http\Controllers\Api\MatchController::class,"matchStageStopTimeV2"]);
//用户报名v2==新版赛事
    Route::post('/v2/match/user/sign',[\App\Http\Controllers\Api\MatchController::class,"matchUserSignV2"]);
//用户取消报名v2==新版赛事
    Route::post('/v2/match/user/sign/out',[\App\Http\Controllers\Api\MatchController::class,"matchUserSignOutV2"]);
//排行榜v2（个人排行榜）==新版赛事
    Route::post('/v2/match/personal/leaderboard',[\App\Http\Controllers\Api\MatchController::class,"matchPersonalLeaderboardV2"]);
//排行榜v2（团队标签列表排行榜）==新版赛事
    Route::post('/v2/match/teamList/leaderboard',[\App\Http\Controllers\Api\MatchController::class,"matchTeamListLeaderboardV2"]);
//排行榜v2（团队标签详情排行榜）==新版赛事
    Route::post('/v2/match/teamDetails/leaderboard',[\App\Http\Controllers\Api\MatchController::class,"matchTeamDetailsLeaderboardV2"]);
//获取GO页面赛事hot轮播
    Route::post('/v2/match/getGoMatchHot',[\App\Http\Controllers\Api\MatchController::class,"getGoMatchHot"]);
//我的报名赛事列表v2==新版赛事
    Route::post('/v2/match/userList',[\App\Http\Controllers\Api\MatchController::class,"matchUserListV2"]);

    //本地运动数据上传V2==新版赛事
    Route::post('/v2/match/postUploadLocalPlayV2',[\App\Http\Controllers\Api\LocalPlay\LocalPlayController::class,"postUploadLocalPlayV2"]);
    //本地运动数据上传+队列V3==新版赛事
    Route::post('/v2/match/postUploadLocalPlayV3',[\App\Http\Controllers\Api\LocalPlay\LocalPlayController::class,"postUploadLocalPlayV3"]);

    //统计用户今天的总运动距离
    Route::post('/my/day/getDistanceSum',[\App\Http\Controllers\Api\UserPlayController::class,"getDistanceSum"]);
    //统计用户指定月份的每天总运动距离
    Route::post('/my/month/getMonthDistanceSum',[\App\Http\Controllers\Api\UserPlayController::class,"getMonthDistanceSum"]);

    //例赛==获取例赛列表
    Route::post('/match/get-regular-season-list',[\App\Http\Controllers\Api\MatchController::class,"getRegularSeasonList"]);
    //赛事==获取我的赛事列表
    Route::post('/match/get-my-match-list',[\App\Http\Controllers\Api\MatchController::class,"getMyMatchList"]);


/*************************************************** 用户目标打卡 **********************************************************/
    //添加/编辑用户打卡目标
    Route::post('/my/writeTargetPunch',[\App\Http\Controllers\Api\Users\UserTargetPunchController::class,"writeTargetPunch"]);
    //获取用户所有打卡目标
    Route::post('/my/getTargetPunch',[\App\Http\Controllers\Api\Users\UserTargetPunchController::class,"getTargetPunch"]);
    //获取用户PK胜率
    Route::post('/my/getUserPkWinRate',[\App\Http\Controllers\Api\UserRankListController::class,"getUserPkWinRate"]);


/*************************************************** 用户账号注销 **********************************************************/
    //用户账号注销
    Route::any('/my/accountCancel',[\App\Http\Controllers\Api\UserController::class,"accountCancel"]);



/*************************************************** 战队 **********************************************************/
    //申请战队
    Route::post('/clan/postApplyTeam',[\App\Http\Controllers\Api\Clans\ClanController::class,"postApplyTeam"]);
    //编辑战队
    Route::post('/clan/editUserClan',[\App\Http\Controllers\Api\Clans\ClanController::class,"editUserClan"]);
    //申请加入战队
    Route::post('/clan/postApplyJoinClan',[\App\Http\Controllers\Api\Clans\ClanController::class,"postApplyJoinClan"]);
    //取消申请加入战队
    Route::post('/clan/postUnJoinClan',[\App\Http\Controllers\Api\Clans\ClanController::class,"postUnJoinClan"]);
    //获取战队详情
    Route::post('/clan/getUserClanInfo',[\App\Http\Controllers\Api\Clans\ClanController::class,"getUserClanInfo"]);
    //获取战队详情==不含战队成绩
    Route::post('/clan/get-user-clan-info-v2',[\App\Http\Controllers\Api\Clans\ClanController::class,"getUserClanInfoV2"]);
    //获取战队详情==战队成绩
    Route::post('/clan/get-user-clan-info-avg-achievement',[\App\Http\Controllers\Api\Clans\ClanController::class,"getUserClanInfoAvgAchievement"]);
    //根据战队ID获取战队成员或待审核成员
    Route::post('/clan/getClanMemberList',[\App\Http\Controllers\Api\Clans\ClanController::class,"getClanMemberList"]);
    //队长审核战队成员
    Route::post('/clan/postReviewApplyClanMember',[\App\Http\Controllers\Api\Clans\ClanController::class,"postReviewApplyClanMember"]);
    //取消申请加入战队
    Route::post('/clan/withdrawClan',[\App\Http\Controllers\Api\Clans\ClanController::class,"withdrawClan"]);
    //移交队长
    Route::post('/clan/postHandoverClanLeader',[\App\Http\Controllers\Api\Clans\ClanController::class,"postHandoverClanLeader"]);
    //获取战队排行榜
    Route::post('/clan/getClanRankingList',[\App\Http\Controllers\Api\Clans\ClanController::class,"getClanRankingList"]);
    //获取战队详情列表
    Route::post('/clan/getClanDetailsList',[\App\Http\Controllers\Api\Clans\ClanController::class,"getClanDetailsList"]);
    //获取他人资料
    Route::post('/clan/getUserOthersInfo',[\App\Http\Controllers\Api\Clans\ClanController::class,"getUserOthersInfo"]);
    //删除战队成员
    Route::post('/clan/delUserClanMember',[\App\Http\Controllers\Api\Clans\ClanController::class,"delUserClanMember"]);
    //新增、批量新增战队相片
//    Route::post('/clan/addPhoto',[\App\Http\Controllers\Api\Clans\ClanPhotoController::class,"addPhoto"]);
    //删除、批量删除战队相片
//    Route::post('/clan/delPhoto',[\App\Http\Controllers\Api\Clans\ClanPhotoController::class,"delPhoto"]);

/*************************************************** 摇加油积分提现 **********************************************************/
    //获取平台支付渠道列表
    Route::post('/shake/get-pay-channel',[\App\Http\Controllers\Api\Users\UserPayAccountController::class,"getPayChannel"]);
    //新增用户支付渠道
    Route::post('/shake/add-user-pay-account',[\App\Http\Controllers\Api\Users\UserPayAccountController::class,"addUserPayAccount"]);
    //编辑用户支付渠道
    Route::post('/shake/edit-user-pay-account',[\App\Http\Controllers\Api\Users\UserPayAccountController::class,"editUserPayAccount"]);
    //用户删除用户支付渠道
    Route::post('/shake/del-user-pay-account',[\App\Http\Controllers\Api\Users\UserPayAccountController::class,"delUserPayAccount"]);
    //用户获取用户支付渠道列表
    Route::post('/shake/get-user-pay-account-list',[\App\Http\Controllers\Api\Users\UserPayAccountController::class,"getUserPayAccountList"]);
    //积分提现
    Route::post('/shake/points-withdrawal',[\App\Http\Controllers\Api\PointsWithdrawal\PointsWithdrawalController::class,"pointsWithdrawal"]);
    //提现支付状态查询
    Route::post('/shake/withdrawal-status-query',[\App\Http\Controllers\Api\PointsWithdrawal\PointsWithdrawalController::class,"withdrawalStatusQuery"]);
    //获取积分详情
    Route::post('/shake/get-points-details',[\App\Http\Controllers\Api\Users\UserPayAccountController::class,"getPointsDetails"]);

});


//校验用户支付信息
Route::post('/user/members/check',[\App\Http\Controllers\Api\UserMembersController::class,"postUserMembersCheck"]);
//用户提交会员注册信息
Route::post('/user/members',[\App\Http\Controllers\Api\UserMembersController::class,"postUserMembers"]);

//微信支付下单
Route::get('/wxpay',[\App\Http\Controllers\Api\UserMembersController::class,"wxPay"]);
//查询订单是否支付
Route::get('/info',[\App\Http\Controllers\Api\UserMembersController::class,"orderInfo"]);
//通过手机号查询用户信息
Route::get('/website/user/info',[\App\Http\Controllers\Api\UserMembersController::class,"getUserInfo"]);

//官网首页获取视频
Route::post('/website/home/video',[\App\Http\Controllers\Api\WebSiteController::class,"postWebsiteHomeVideo"]);


/*********************************************** 用户端-积分兑换 *************************************************/
//获取积分二维码信息
Route::post('/members/redeem/getQRCodeInfo',[\App\Http\Controllers\Api\Members\RedeemController::class,'getQRCodeInfo']);
//获取用户积分来源
Route::post('/members/redeem/getSourceOfPoints',[\App\Http\Controllers\Api\Members\RedeemController::class,'getSourceOfPoints']);
//获取用户积分兑换记录
Route::post('/members/redeem/getRedeemBill',[\App\Http\Controllers\Api\Members\RedeemController::class,'getRedeemBill']);
//根据ID获取用户积分来源
Route::post('/members/redeem/getUserIntegralLogDetail',[\App\Http\Controllers\Api\Members\RedeemController::class,'getUserIntegralLogDetail']);
//根据ID获取用户积分兑换详情
Route::post('/members/redeem/getBrandRedeemLogDetail',[\App\Http\Controllers\Api\Members\RedeemController::class,'getBrandRedeemLogDetail']);


/*********************************************** 商户端-积分兑换 *************************************************/
//手机短息登录商户
Route::post('/merchants/redeem/postLoginPhone',[\App\Http\Controllers\Api\Merchants\RedeemController::class,'postLoginPhone']);
//获取品牌分店信息
Route::post('/merchants/redeem/getBrandShopInfo',[\App\Http\Controllers\Api\Merchants\RedeemController::class,'getBrandShopInfo']);
//根据ID获取用户头像和昵称
Route::post('/merchants/redeem/getUsrUserInfo',[\App\Http\Controllers\Api\Merchants\RedeemController::class,'getUsrUserInfo']);
//商户端-积分兑换
Route::post('/merchants/redeem/postRedeem',[\App\Http\Controllers\Api\Merchants\RedeemController::class,'postRedeem']);
//获取积分兑换账单 type：1入账单 2出账单
Route::post('/merchants/redeem/getRedeemBill',[\App\Http\Controllers\Api\Merchants\RedeemController::class,'getRedeemBill']);
//根据ID获取账单详情
Route::post('/merchants/redeem/getBrandRedeemLogDetail',[\App\Http\Controllers\Api\Merchants\RedeemController::class,'getBrandRedeemLogDetail']);
//GO介绍
Route::post('/common/setting/setGoIntroduce',[\App\Http\Controllers\Api\Settings\SettingController::class,'setGoIntroduce']);

//更新摇加油，定时任务
Route::any('/crontab/updateShakeStatus',[App\Http\Controllers\Api\Shake\TestShakeController::class,'updateShakeStatus']);



/*********************************************** 全云动API-赛事改版 (公共接口)*************************************************/
//获取报名团队标签
Route::post('/v2/match/getSignUpTeamTag',[App\Http\Controllers\Api\MatchController::class,'getSignUpTeamTag']);
//获取赛事列表统计个状态的数量
Route::post('/v2/match/getMatchNum',[\App\Http\Controllers\Api\MatchController::class,"getMatchNum"]);
//数据上传接口切换v2/v3
Route::any('/v2/match/getPalyUrl',[\App\Http\Controllers\Api\MatchController::class,"getPalyUrl"]);



//关联赛事插入用户参赛假数据
Route::post('/match/sysMatchInsertFakeData',[\App\Http\Controllers\Api\MatchV2\MatchV2Controller::class,"sysMatchInsertFakeData"]);

/****************************** 全云动API-获取banner (公共接口)**********************************************/
//获取banner图
Route::post('/banner/getBannerList',[\App\Http\Controllers\Api\BannerUrl\BannerUrlController::class,"getBannerList"]);
//手动执行用户目标打卡统计
Route::any('/console/getUserTargetPunchsConsoleList',[\App\Http\Controllers\Api\Users\UserTargetPunchController::class,'getUserTargetPunchsConsoleList']);
//获取打榜模式简介与PK模式简介
Route::any('/introduce/getRuleIntroduce',[\App\Http\Controllers\Api\UserRankListController::class,'getRuleIntroduce']);

/****************************** 全云动API-获取战队 (公共接口)**********************************************/
//获取战队列表
Route::any('/clan/getClanList',[\App\Http\Controllers\Api\Clans\ClanController::class,'getClanList']);
//获取战队模块介绍信息
Route::any('/clan/getClansIntroduction',[\App\Http\Controllers\Api\Clans\ClanController::class,'getClansIntroduction']);
//图片上传公共接口
Route::any('/images/upload',[\App\Http\Controllers\Admin\MatchsController::class,"getMatchsUpload"]);
Route::any('/v2/images/upload',[App\Http\Controllers\Api\Uploads\UploadFileController::class,'uploadImages']);


//补全摇加油数据缺失
Route::any('/my/completionShake',[\App\Http\Controllers\Api\Shake\ShakeController::class,'completionShake']);
//摇加油积分系数修改
Route::any('/shake/updateEachIntegral',[\App\Http\Controllers\Api\Shake\ShakeController::class,'updateEachIntegral']);

//批量处理PK结果数据错误问题
//Route::any('/my/getPkList',[\App\Http\Controllers\Api\Users\UserTargetPunchController::class,'getPkList']);


/****************************** 全云动API-有赞商城API对接 (公共接口)**********************************************/
//Route::any('/youzan/callback',[\App\Http\Controllers\Api\Youzan\YouzanController::class,'callback']);
//给用户增加积分
Route::any('/youzan/increaseUserPoints',[\App\Http\Controllers\Api\Youzan\YouzanController::class,'increaseUserPoints']);
//给用户减积分
Route::any('/youzan/decreaseUserPoints',[\App\Http\Controllers\Api\Youzan\YouzanController::class,'decreaseUserPoints']);
//创建客户到店铺
Route::any('/youzan/createCustomer',[\App\Http\Controllers\Api\Youzan\YouzanController::class,'createCustomer']);
//有赞消息推送回调地址
Route::any('/youzanyun/news-push',[\App\Http\Controllers\Api\Youzan\YouzanController::class,'newsPush']);
//同步客户积分
Route::any('/youzanyun/sync-user-points',[\App\Http\Controllers\Api\Youzan\YouzanController::class,'syncUserPoints']);

/****************************** 全云动API-支付宝异步回调通知接收 (公共接口)**********************************************/
//接收支付宝异步通知消息
Route::any('/alipay/notify-url',[\App\Http\Controllers\Api\PointsWithdrawal\PointsWithdrawalController::class,'notifyUrl']);
//接收支付宝异步通知消息
Route::any('/alipay/test',[\App\Http\Controllers\Api\PointsWithdrawal\TestController::class,'test']);
//支付宝授权回调地址
Route::any('/alipay/asynurl',[\App\Http\Controllers\Api\PointsWithdrawal\PointsWithdrawalController::class,'alipayAsynurl']);


/****************************** 全云动API-Redis查询 (公共接口)**********************************************/
//获取between在Redis用户hkeys提交时间列表
Route::any('/redis/get-cace-post-between-play-list',[\App\Http\Controllers\Api\UserPlayController::class,"getCacePostBetweenPlayList"]);





