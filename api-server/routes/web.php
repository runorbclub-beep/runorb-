<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


//产品视频列表
Route::get('/product/video', [\App\Http\Controllers\Web\HomeController::class,"getProductVideo"]);

//官网首页查询
Route::get('/home', [\App\Http\Controllers\Web\HomeController::class,"getHome"]);


//排行榜 公共数据
Route::get('/rank/list', [\App\Http\Controllers\Web\RankListController::class,"getRankList"]);

//排行榜 公共数据
Route::get('/rank/list/v2', [\App\Http\Controllers\Web\RankListController::class,"getRankListV2"]);

//app下载
Route::get('/app', [\App\Http\Controllers\Web\AppController::class,"getVersion"]);

//关于我们
Route::get('/aboutme', [\App\Http\Controllers\Web\AboutmeController::class,"getAboutme"]);

//文章列表
Route::get('/new/list', [\App\Http\Controllers\Web\WebsiteNewsController::class,"newsList"]);

//文章详情
Route::get('/new/info', [\App\Http\Controllers\Web\WebsiteNewsController::class,"postNewsInfo"]);

//赛事列表
Route::get('/match/list', [\App\Http\Controllers\Web\MatchsController::class,"matchList"]);

//赛事项目列表
Route::get('/match/event/list', [\App\Http\Controllers\Web\MatchsController::class,"matchEventList"]);


//赛事详情
Route::get('/match/info', [\App\Http\Controllers\Web\MatchsController::class,"matchInfo"]);
//赛事赛段列表
Route::get('/match/stage/list', [\App\Http\Controllers\Web\MatchsController::class,"matchStage"]);
//赛事赛段排名
Route::get('/match/stage/ranking', [\App\Http\Controllers\Web\MatchsController::class,"matchStageRanking"]);


//官网自定义排名
Route::get('/match/ranking', [\App\Http\Controllers\Web\MatchRankingController::class,"getMatchRanking"]);

//官网查询榜单类型列表
Route::get('/ranking/list', [\App\Http\Controllers\Web\MatchRankingController::class,"getRankingTypeList"]);

//官网查询榜单类型列表
Route::get('/members/description/info', [\App\Http\Controllers\Web\HomeController::class,"getMembersDescriptionInfo"]);


//获取地区数据
Route::get('/getRegion', [\App\Http\Controllers\Web\RegionController::class,"getRegion"]);

//活动列表
Route::get('/activity/list', [\App\Http\Controllers\Web\WebsiteActivityController::class,"activityList"]);

//活动详情
Route::get('/activity/info', [\App\Http\Controllers\Web\WebsiteActivityController::class,"postActivityInfo"]);


//企业摇加油-获取员工
Route::get('/shake/exportShakeUser', [\App\Http\Controllers\Web\Shake\QiyeShakeController::class,"exportShakeUser"]);

//企业摇加油-获取员工
Route::any('/xz/userPlayExport', [\App\Http\Controllers\Web\UserPlayExportController::class,"userPlayExport"]);

//企业摇跑指数-获取员工
Route::any('/xz/userAchievementExport', [\App\Http\Controllers\Web\UserPlayExportController::class,"userAchievementExport"]);

//用户导出，用于有赞导入
Route::any('/xz/usrUserExport', [\App\Http\Controllers\Web\UserPlayExportController::class,"usrUserExport"]);

//导出全国总部四项赛事排名Excel表
Route::any('/xz/downloadRanking', [\App\Http\Controllers\Web\UserPlayExportController::class,"downloadRanking"]);

//摇加油积分排名Excel表
Route::any('/xz/download-shake-integral-ranking', [\App\Http\Controllers\Web\UserPlayExportController::class,"downloadShakeIntegralRanking"]);



