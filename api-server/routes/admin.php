<?php

use Illuminate\Support\Facades\Route;

Route::get("/test",[\App\Http\Controllers\TestController::class,"getTest"]);
Route::any("/test/del",[\App\Http\Controllers\TestController::class,"delMatchsUserGrade"]);


//用户登录
Route::post('/user/login',[\App\Http\Controllers\Admin\AdminUserController::class,"postUserLogin"]);

//上传赛事宣传图
Route::any('/match/upload',[\App\Http\Controllers\Admin\MatchsController::class,"getMatchsUpload"]);



//token 拦截验证
Route::middleware(['admin_certification'])->group(function () {

//    首页查询
    Route::post('/home',[App\Http\Controllers\Admin\Home\HomeController::class,"AdminHome"]);


//查询用户信息
    Route::post('/user/info',[\App\Http\Controllers\Admin\AdminUserController::class,"getUserInfo"]);
//用户退出登录
    Route::post('/auth/logout',[\App\Http\Controllers\Admin\AdminUserController::class,"postAuthLogout"]);

//上传徽章文件
    Route::any('/medal/upload',[\App\Http\Controllers\Admin\MedalController::class,"getMedalUpload"]);
//创建徽章
    Route::post('/medal/add',[\App\Http\Controllers\Admin\MedalController::class,"postMedalAdd"]);


//查询赛段晋级规则
    Route::post('/match/stage/rules/list',[\App\Http\Controllers\Admin\MatchsStageController::class,"postMatchStageRulesList"]);
//创建赛段晋级规则
    Route::post('/match/stage/rules/add',[\App\Http\Controllers\Admin\MatchsStageController::class,"postMatchStageRulesAdd"]);
//查询赛段晋级规则信息
    Route::post('/match/stage/rules/info',[\App\Http\Controllers\Admin\MatchsStageController::class,"postMatchStageRulesInfo"]);
//删除赛段晋级规则
    Route::post('/match/stage/rules/delete',[\App\Http\Controllers\Admin\MatchsStageController::class,"postMatchStageRulesDelete"]);

//新增比赛项目类型
    Route::post('/match/event/type/add',[\App\Http\Controllers\Admin\MatchsEventTypeController::class,"postMatchEventTypeAdd"]);
//查询比赛项目列表
    Route::post('/match/event/type/list',[\App\Http\Controllers\Admin\MatchEventTypeController::class,"postMatchEventTypeList"]);
//删除比赛项目
    Route::post('/match/event/type/delete',[\App\Http\Controllers\Admin\MatchEventTypeController::class,"postMatchEventTypeDelete"]);
//删除比赛项目
    Route::post('/match/event/type/index/update',[\App\Http\Controllers\Admin\MatchsEventTypeController::class,"postMatchEventTypeIndexUpdate"]);

//新增比赛类型
    Route::post('/match/type/add',[\App\Http\Controllers\Admin\MatchsTypeController::class,"postMatchTypeAdd"]);
//查询赛事类型列表
    Route::post('/match/type/list',[\App\Http\Controllers\Admin\MatchsTypeController::class,"postMatchTypeList"]);
//删除赛事类型
    Route::post('/match/type/delete',[\App\Http\Controllers\Admin\MatchsTypeController::class,"postMatchTypeDelete"]);

//新增赛事积分规则
    Route::post('/match/integral/rule/add',[\App\Http\Controllers\Admin\MatchsIntegralRuleController::class,"postMatchIntegralRuleAdd"]);
//查询赛事积分信息
    Route::post('/match/integral/rule/info',[\App\Http\Controllers\Admin\MatchsIntegralRuleController::class,"postMatchIntegralRuleInfo"]);
//查询赛事积分列表
    Route::post('/match/integral/rule/list',[\App\Http\Controllers\Admin\MatchsIntegralRuleController::class,"postMatchIntegralRuleList"]);
//删除赛事积分
    Route::post('/match/integral/rule/delete',[\App\Http\Controllers\Admin\MatchsIntegralRuleController::class,"postMatchIntegralRuleDelete"]);

//创建赛事
    Route::post('/match/add',[\App\Http\Controllers\Admin\MatchsController::class,"getMatchsAdd"]);
//赛事列表
    Route::post('/match/list',[\App\Http\Controllers\Admin\MatchsController::class,"getMatchsList"]);
//赛事详情
    Route::post('/match/info',[\App\Http\Controllers\Admin\MatchsController::class,"getMatchsInfo"]);
//删除赛事
    Route::post('/match/delete',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchsDelete"]);
//发布赛事
    Route::post('/match/release',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchsRelease"]);
//取消发布赛事
    Route::post('/match/unrelease',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchsUnRelease"]);
//获取赛事标题列表
    Route::post('/match/title/list',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchTitleList"]);
//获取赛事宣传图标题
    Route::post('/match/banner/title/list',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchBannerTitleList"]);

//赛事新增比赛项目
    Route::post('/match/event/add',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchEventAdd"]);
//赛事新增比赛项目
    Route::post('/match/event/delete',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchEventDelete"]);

//赛事项目新增赛段
    Route::post('/match/events/stages/add',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchEventStageAdd"]);
//赛事项目删除赛段
    Route::post('/match/events/stages/delete',[\App\Http\Controllers\Admin\MatchsController::class,"postMatchEventStageDelete"]);

//管理后台添加用户到赛事报名列表
    Route::post('/match/join/user',[\App\Http\Controllers\Admin\MatchsController::class,"matchBannerDelete"]);


//APP赛事宣传图
    Route::post('/match/banner/list',[\App\Http\Controllers\Admin\MatchsController::class,"matchBannerList"]);
//新增APP赛事宣传图
    Route::post('/match/banner/add',[\App\Http\Controllers\Admin\MatchsController::class,"matchBannerAdd"]);
//删除APP赛事宣传图
    Route::post('/match/banner/delete',[\App\Http\Controllers\Admin\MatchsController::class,"matchBannerDelete"]);


//查询用户类型字典
    Route::post('/sys/user/type/list',[\App\Http\Controllers\Admin\SysUserTypeController::class,"postSysUserTypeList"]);
//查询用户性别字典match/info
    Route::post('/sys/user/sex/list',[\App\Http\Controllers\Admin\SysUserTypeController::class,"postSysSexList"]);

//新增 编辑新闻
    Route::post('/news/add',[\App\Http\Controllers\Admin\News\NewsController::class,"postNewsAdd"]);

//查询news
    Route::post('/news/info',[\App\Http\Controllers\Admin\News\NewsController::class,"postNewsInfo"]);

//查询最新前十news
    Route::post('/news/list',[\App\Http\Controllers\Admin\News\NewsController::class,"postNewsList"]);

//删除news
    Route::post('/news/delete',[\App\Http\Controllers\Admin\News\NewsController::class,"postNwesDelete"]);


//    用户团队列表
    Route::post('/group/list',[\App\Http\Controllers\Admin\Group\GroupCrontroller::class,"postGroupList"]);
//    团队新增，编辑
    Route::post('/group/add',[\App\Http\Controllers\Admin\Group\GroupCrontroller::class,"postGroupAdd"]);
//    用户加入到团队
    Route::post('/group/join/user',[\App\Http\Controllers\Admin\Group\GroupCrontroller::class,"postGroupJoinUser"]);
//    用户从团队删除
    Route::post('/group/delete/user',[\App\Http\Controllers\Admin\Group\GroupCrontroller::class,"postGroupDeleteUser"]);
//    团队下的用户列表
    Route::post('/group/user/list',[\App\Http\Controllers\Admin\Group\GroupCrontroller::class,"postGroupUserList"]);


//    新增，编辑APP版本
//    新增，编辑APP版本
    Route::post('/app/version/add',[\App\Http\Controllers\Admin\Website\AppController::class,"postAddVersion"]);
//    查询版本列表
    Route::post('/app/version/list',[\App\Http\Controllers\Admin\Website\AppController::class,"postAppVersionList"]);
//    删除版本
    Route::post('/app/version/delete',[\App\Http\Controllers\Admin\Website\AppController::class,"postAppVersionDelete"]);
//    新增。编辑，关于我们
    Route::post('/aboutme/add',[\App\Http\Controllers\Admin\Website\AboutmeController::class,"postAboutmeAdd"]);
//    删除，关于我们
    Route::post('/aboutme/delete',[\App\Http\Controllers\Admin\Website\AppController::class,"postAboutmeDelete"]);
//    关于我们，列表
    Route::post('/aboutme/list',[\App\Http\Controllers\Admin\Website\AppController::class,"postAboutmeList"]);



//    首页内容列表
    Route::post('/home/list',[\App\Http\Controllers\Admin\Website\HomeController::class,"postHomeList"]);
//    新增、编辑官网首页
    Route::post('/home/add',[\App\Http\Controllers\Admin\Website\HomeController::class,"postHomeContentAdd"]);
//    官网首页内容排序
    Route::post('/home/index/update',[\App\Http\Controllers\Admin\Website\HomeController::class,"postWebsiteHomeIndexUpdate"]);
//    删除官网首页内容
    Route::post('/home/delete',[\App\Http\Controllers\Admin\Website\HomeController::class,"postWebsiteHomeDelete"]);



//    查询系统配置
    Route::post('/system',[\App\Http\Controllers\Admin\System\SystemController::class,"postSystemSetting"]);
//    查询系统配置
    Route::post('/system/update',[\App\Http\Controllers\Admin\System\SystemController::class,"postSystemSettingUpdate"]);
//    查询榜单类型配置
    Route::post('/system/ranking/list',[\App\Http\Controllers\Admin\System\SystemController::class,"postRankingList"]);
//    查询榜单类型配置
    Route::post('/system/ranking/update',[\App\Http\Controllers\Admin\System\SystemController::class,"postRankingUpdate"]);


//    用户列表查询
    Route::post('/user/list',[\App\Http\Controllers\Admin\User\UserController::class,"postUserList"]);
//    用户运动数据
    Route::post('/user/play/info',[\App\Http\Controllers\Admin\User\UserController::class,"postUserPlayInfo"]);
//    用户详情
    Route::post('/user/detail',[\App\Http\Controllers\Admin\User\UserController::class,"postUserInfo"]);
//    用户信息修改
    Route::post('/user/edit',[\App\Http\Controllers\Admin\User\UserController::class,"postUserEdit"]);
//    用户运动列表
    Route::post('/user/play/list',[\App\Http\Controllers\Admin\User\UserController::class,"postUserPlayList"]);



//    赛事赛段下的用户列表
    Route::post('/matchs/stage/user/list',[\App\Http\Controllers\Admin\Matchs\MatchsInfoController::class,"MatchsStageUserList"]);
//    赛事赛段下的用户列表
    Route::post('/matchs/stage/user/play/list',[\App\Http\Controllers\Admin\Matchs\MatchsInfoController::class,"MatchsStageUserPlayList"]);


//    获取系统筛选条件
    Route::post('/play/check/data',[\App\Http\Controllers\Admin\Play\PlayController::class,"postPlayCheckData"]);
//    系统运动列表
    Route::post('/play/list',[\App\Http\Controllers\Admin\Play\PlayController::class,"postUserPlay"]);
//    运动屏蔽
    Route::post('/play/list/delete',[\App\Http\Controllers\Admin\Play\PlayController::class,"postUserPlayDelete"]);
//    系统异常判定列表
    Route::post('/play/abnormal/list',[\App\Http\Controllers\Admin\Play\PlayController::class,"postAbnormalList"]);
//    系统异常判定列表
    Route::post('/play/abnormal/update',[\App\Http\Controllers\Admin\Play\PlayController::class,"postAbnormalUpdate"]);
//    系统运动列表---每日之星
    Route::post('/play/star/list',[\App\Http\Controllers\Admin\Play\PlayController::class,"postUserPlayStar"]);


//    查询会员招募信息
    Route::post('/members',[\App\Http\Controllers\Admin\Members\MembersController::class,"postMembersTitle"]);
//    更新会员招募信息
    Route::post('/members/update',[\App\Http\Controllers\Admin\Members\MembersController::class,"postMembersUpdate"]);
//    变更用户会员信息
    Route::post('/user/join/members/update',[\App\Http\Controllers\Admin\User\UserController::class,"postUserJoinMembers"]);


//    APP 启动页宣传图列表
    Route::post('/sys/app/advertising/list',[\App\Http\Controllers\Admin\System\AppController::class,"postAppAdvertisingList"]);
//    新增启动页广告
    Route::post('/sys/app/advertising/add',[\App\Http\Controllers\Admin\System\AppController::class,"postAppAdvertisingAdd"]);
//    变更启动页广告状态
    Route::post('/sys/app/advertising/update',[\App\Http\Controllers\Admin\System\AppController::class,"postAppAdvertisingUpdate"]);


//    官网赛事榜单列表
    Route::post('/website/match/ranking/list',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingList"]);
//    官网赛事榜单添加
    Route::post('/website/match/ranking/add',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingAdd"]);
//    官网赛事榜单删除
    Route::post('/website/match/ranking/delete',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingDelete"]);
//    官网赛事榜单下的用户列表
    Route::post('/website/match/ranking/user/list',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingDetail"]);
//    用户添加到榜单列表下
    Route::post('/website/match/ranking/user/add',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingUserAdd"]);
//    官网赛事榜单下的用户数据修改
    Route::post('/website/match/ranking/user/update',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingUserUpdate"]);
//    用户榜单删除
    Route::post('/website/match/ranking/user/delete',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingUserDelete"]);

//    榜单下的用户，筛选用户
    Route::post('/website/match/ranking/choose/user/list',[\App\Http\Controllers\Admin\Website\WebMatchRankingController::class,"postWebMatchRankingUserList"]);




//    会员描述维护
    Route::post('/members/description/add',[\App\Http\Controllers\Admin\System\SystemController::class,"postMembersDescriptionAdd"]);
//    会员描述查询
    Route::post('/members/description/info',[\App\Http\Controllers\Admin\System\SystemController::class,"postMembersDescriptionInfo"])->name("membersDescriptionInfo");



    /********************************************************更多活动**********************************************************************/
    //新增 编辑更多活动
    Route::post('/activity/add',[\App\Http\Controllers\Admin\Activity\ActivityController::class,"postActivityAdd"]);
    //查询news
    Route::post('/activity/info',[\App\Http\Controllers\Admin\Activity\ActivityController::class,"postActivityInfo"]);
    //查询最新前十news
    Route::post('/activity/list',[\App\Http\Controllers\Admin\Activity\ActivityController::class,"postActivityList"]);
    //删除news
    Route::post('/activity/delete',[\App\Http\Controllers\Admin\Activity\ActivityController::class,"postActivityDelete"]);

    /*****************************************************企业摇加油***********************************************************/
    //新增 编辑企业摇加油
    Route::post('/qiyeShake/add',[\App\Http\Controllers\Admin\Shake\QiyeShakeController::class,"postQiyeShakeAdd"]);
    //查询企业摇加油
    Route::post('/qiyeShake/info',[\App\Http\Controllers\Admin\Shake\QiyeShakeController::class,"postQiyeShakeInfo"]);
    //查询最新前十企业摇加油
    Route::post('/qiyeShake/list',[\App\Http\Controllers\Admin\Shake\QiyeShakeController::class,"postQiyeShakeList"]);
    //删除企业摇加油
    Route::post('/qiyeShake/delete',[\App\Http\Controllers\Admin\Shake\QiyeShakeController::class,"postQiyeShakeDelete"]);

    /*****************************************************--战队管理--***********************************************************/
    //获取战队待审核审核列表
    Route::any('/clans/getPendingReviewList',[\App\Http\Controllers\Admin\Clans\ClanController::class,"getPendingReviewList"]);
    //审核待审核战队
    Route::any('/clans/postPendingReview',[\App\Http\Controllers\Admin\Clans\ClanController::class,"postPendingReview"]);


});

/********************************************* 积分兑换 ***************************************************************/
//获取品牌列表
Route::post('/redeem/brand/list',[\App\Http\Controllers\Admin\Redeem\BrandController::class,'list']);
//根据品牌ID获取品牌详情
Route::post('/redeem/brand/getBrandDetail',[\App\Http\Controllers\Admin\Redeem\BrandController::class,'getBrandDetail']);
//新增品牌
Route::post('/redeem/brand/add',[\App\Http\Controllers\Admin\Redeem\BrandController::class,'add']);
//编辑品牌
Route::post('/redeem/brand/edit',[\App\Http\Controllers\Admin\Redeem\BrandController::class,'edit']);
//删除品牌
Route::post('/redeem/brand/del',[\App\Http\Controllers\Admin\Redeem\BrandController::class,'del']);

//新增品牌分店
Route::post('/redeem/brand/addBrandShop',[\App\Http\Controllers\Admin\Redeem\BrandShopController::class,'addBrandShop']);
//编辑品牌分店
Route::post('/redeem/brand/editBrandShop',[\App\Http\Controllers\Admin\Redeem\BrandShopController::class,'editBrandShop']);
//删除品牌分店
Route::post('/redeem/brand/delBrandShop',[\App\Http\Controllers\Admin\Redeem\BrandShopController::class,'delBrandShop']);
//根据条件查询品牌分店账单列表 type: 1进账单 2出账单
Route::post('/redeem/brand/getBrandRedeemLog',[\App\Http\Controllers\Admin\Redeem\BrandRedeemLogController::class,'getBrandRedeemLog']);


//根据条件查询品牌分店店员列表
Route::post('/redeem/brand/getBrandUser',[\App\Http\Controllers\Admin\Redeem\BrandUserController::class,'getBrandUser']);
//根据手机号查询店员手机号是否注册全云动账号，进行关联注册
Route::post('/redeem/brand/postBrandUserPhone',[\App\Http\Controllers\Admin\Redeem\BrandUserController::class,'postBrandUserPhone']);
//编辑品牌分店店员信息
Route::post('/redeem/brand/editBrandUser',[\App\Http\Controllers\Admin\Redeem\BrandUserController::class,'editBrandUser']);
//删除品牌分店店员
Route::post('/redeem/brand/delBrandUser',[\App\Http\Controllers\Admin\Redeem\BrandUserController::class,'delBrandUser']);

//新版赛事用户报名==后台管理
Route::post('/match/add-match-user-sign',[\App\Http\Controllers\Admin\MatchsController::class,'addMatchUserSign']);
//根据赛事ID获取赛事团队列表
Route::post('/match/get-team-tag',[\App\Http\Controllers\Admin\MatchsController::class,'getTeamTag']);
//根据赛事ID添加赛事团队列表
Route::post('/match/add-team-tag',[\App\Http\Controllers\Admin\MatchsController::class,'addTeamTag']);


//根据赛事ID获取用户例赛奖章列表
Route::post('/match/get-match-award',[\App\Http\Controllers\Admin\User\UserController::class,'getMatchAward']);
//新增用户例赛奖章
Route::post('/match/add-match-award',[\App\Http\Controllers\Admin\User\UserController::class,'addMatchAward']);
//删除用户例赛奖章
Route::post('/match/del-match-award',[\App\Http\Controllers\Admin\User\UserController::class,'delMatchAward']);
//根据赛事ID获取用户例赛赛点列表
Route::post('/match/get-match-point-list',[\App\Http\Controllers\Admin\User\UserController::class,'getMatchPointList']);
//新增用户例赛赛点/清零赛点/删除报名数据
Route::post('/match/post-match-point',[\App\Http\Controllers\Admin\User\UserController::class,'postMatchPoint']);



