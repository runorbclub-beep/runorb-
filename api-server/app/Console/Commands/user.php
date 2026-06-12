<?php

namespace App\Console\Commands;

use App\Http\Controllers\PublicFunction\StaticDataController;
use App\Models\UsrUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class user extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        Redis::select(1);
        $_sys_this_year = Redis::select("sys_this_year");

        $_this_day_start_time = time();
        $_this_day_stop_time = strtotime(date("Y-m-d",$_this_day_start_time)." 23:59:59");

        $_this_year = date("Y",$_this_day_start_time);



//        如果年份切换，用户相关信息编辑次数限制重置
        if($_sys_this_year != $_this_year){
//            从reds获取默认配置
            $_arrOfUserDetailChange = Redis::hgetall("user_detail_change_num");

//            若没有配置，获取系统默认配置
            if(count($_arrOfUserDetailChange) == 0){
                $_arrOfUserDetailChange = StaticDataController::$_user_detail_change_num;
                foreach ($_arrOfUserDetailChange as $key=>$value){
                    Redis::hset("user_detail_change_num",$key,$value);
                }
            }

//            重置用户信息变更次数限制
            UsrUser::where(["status"=>1])->update($_arrOfUserDetailChange);
        }


//        用户生日遍历，区分青少年，成年
        $_start_birthday = date("Y-m-d",time()-StaticDataController::$_yang_stop_age*(60*60*24*365));
        $_stop_birthday = date("Y-m-d",time()-StaticDataController::$_yang_start_age*(60*60*24*365));

//        查询年龄在青年范围内，但不是青年的用户,变更为青年
        UsrUser::where([
            "status"=>1,
            "is_yang"=>0,
        ])->where("birthday",">=",$_start_birthday)->where("birthday","<=",$_stop_birthday)->update(["is_yang"=>1]);


        //查询年龄不在青年范围内，但是青年的用户,变为非青年
        UsrUser::where([
            "status"=>1,
            "is_yang"=>1,
        ])->where("birthday","<",$_start_birthday)->where("birthday",">",$_stop_birthday)->update(["is_yang"=>0]);

//        用户会员状态变更，区分已过期
        UsrUser::where([
            "status"=>1,
            "is_members"=>1,
        ])->where("members_exptime","<",$_this_day_stop_time)->update([
            "is_members"=>0,
            "members_status"=>3,
        ]);

        return true;
    }
}
