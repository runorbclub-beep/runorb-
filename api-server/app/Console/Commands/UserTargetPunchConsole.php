<?php

namespace App\Console\Commands;

use App\Models\UserTargetPunch;
use App\Services\UserTargetPunchService;
use Illuminate\Console\Command;

/**
 * 用户目标打开定时统计任务
 * Class UserTargetPunchConsole
 * @package App\Console\Commands
 * User: zxw
 * Date: 2021/11/25 15:54
 */
class UserTargetPunchConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:UserTargetPunch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用户目标打开定时统计任务';

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
     * @return int
     */
    public function handle()
    {
        $this->line('开始---执行用户目标打开定时统计任务'.date('Y-m-d H:i:s'));
        $userTargetPunchService = new UserTargetPunchService();
        $userTargetPunchService->getUserTargetPunchsConsoleList();
        $this->line('完成--执行用户目标打开定时统计任务'.date('Y-m-d H:i:s'));
    }
}
