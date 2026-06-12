<?php


namespace App\Constants;


class SettingMessage
{
    //摇跑四项赛-赛事项目类型ID（matchs_event_type 表同步）
    const matchs_event_type_id = 102231304350732288;

    const SET_PLAYV3_TYPE = [2,3,5];//过滤PK、摇加油、随手摇等模式下得数据在V3上传结果接口上榜

    //有赞云配置
    const SET_YOU_ZAN_URL = "https://open.youzanyun.com";
    const SET_CLIENT_ID = "71f7c3f952f3fe0645";//有赞云颁发给开发者的应用ID
    const SET_CLIENT_SECRET = "95ab23d12c20d26518e8568786b86847";//有赞云颁发给开发者的应用secret
    const SET_AUTHORIZE_TYPE = "silent";//授权方式（固定为 “silent”）
    const SET_GRANT_ID = 94761702;//100911735;94761702;//授权店铺id（即kdt_id），API接口对接传店铺id，支付商户对接传mchId

    //商户端-积分兑换--订单号前缀
    const SET_MERCHANT_ORDER_NO_PREFIX = "JFDH-";
    //有赞端-积分打通--订单号前缀
    const SET_YOUZAN_ORDER_NO_PREFIX = "YOUZAN-";


    //GO介绍-中文
    const SET_GO_INTRODUCE_ZH_CN = "随手摇\n随时随地，多少随意! \n\n摇跑打榜\n刷4项摇跑排行，测摇跑指数! \n\n摇跑pk\n单挑不惧，组队更猛!\n\n摇加油\n每天一场，身强运佳！\n\n摇跑赛事\n云端大赛！跨越空间！";

    //GO介绍-英文
    const SET_GO_INTRODUCE_EN_US = "Free style\nAnytime, anywhere, more or less at will.\n\nRanking mode\nChallenge 4 rankings, test your YPI.\n\nPK mode\nPairs PK or Team PK.\n\nUp Up\nOne game a day,ready to get score.\n\nMatch mode\nCloud contest! Across the ear";

    const team_name = "中国体育协会职工足球队";//四项赛事名称

    const quartets_icon = "matchs_image/matchs_sources/quartets_icon.png";//四项赛事icon

    const ranking_img = "matchs_image/matchs_sources/ranking_img.png";//排行榜banner

    const ranking_img_en = "matchs_image/matchs_sources/ranking_img_en.png";//排行榜banner英文

    //新版赛事报名团队标签-中文
    const TEAM_TAG_ZH_CN = [
        '大连湾（种子队）','五要（种子队）','沈飞', '大连西岗','南山足协1','中国电科','河池赛区','贵册前卫', '内蒙古消防（种子队）', '大戍律所（种子队）', '开注国和（种子队）', '东风本田', '内蒙古公安厅', '工信部五所','中建西南院', '陕西建工', '西安碑林', '南山足协2', '呼和浩特嚴沃', '烟台开发区', '天津前卫（种子队）', '靈庆前卫（种子队）', '火车头1 （种子队）', '江苏电信', '盖州', '北京联通', '新华联', '新余钢', '火车头2', '森工体协', '火车头3', '晋煤', '潞安太行（种子队）', '中国电子（种子队）',
        '中国锅硬（种子队）', '四川前卫', '内蒙古庆源', '中建一局', '大庆石化', '辽阳', '开原', '扬州', '其他',
    ];

    //数据上传接口切换v2/v3
    const GET_APP_V_URL = [
        'code' => 1,
        'msg' => 'success',
        'data' => [
            'edition' => 'v3',
            'domain_name' => "https://api.runorb.us",
            'paly_url' => "/api/v2/match/postUploadLocalPlayV3",
            'is_socket' => 0,
        ]
    ];

}
