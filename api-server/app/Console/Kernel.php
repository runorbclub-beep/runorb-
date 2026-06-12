<?php

namespace App\Console;

use App\Console\Commands\Shake;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        每分钟运行一次，赛事状态变更
         $schedule->command('command:Runball')->everyMinute();

//        每分钟运行一次，用户目标打开定时统计任务
        $schedule->command('command:UserTargetPunch')->everyMinute();

//         每分钟运行一次，用户V3数据上传队列失败任务弥补定时任务
        $schedule->command('command:LocalPlayConsole')->everyMinute();

//         每天凌晨运行一次，用户相关数据变更
         $schedule->command('command:user')->daily();

        //每天凌晨运行一次，创建摇加油比赛
        $schedule->call(function () {
            Shake::createShake();
        })->dailyAt('03:00');
        // })->everyMinute();
        //每分钟运行一次，开始和结束和开始报名
        $schedule->call(function () {
            Shake::changeShakeStatus();
        })->everyMinute()->between('5:30', '23:50');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
