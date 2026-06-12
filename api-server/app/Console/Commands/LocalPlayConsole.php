<?php

namespace App\Console\Commands;

use App\Services\CrontabService;
use Illuminate\Console\Command;

/**
 * 用户V3数据上传队列失败任务弥补定时任务
 * Class LocalPlayConsole
 * @package App\Console\Commands
 * User: zxw
 * Date: 2021/12/21 14:09
 */
class LocalPlayConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:LocalPlayConsole';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用户V3数据上传队列失败任务弥补定时任务';

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
     * @return void
     */
    public function handle()
    {
        $this->line('开始---执行用户V3数据上传队列失败任务弥补定时任务'.date('Y-m-d H:i:s'));
        CrontabService::localPlayConsole();
        $this->line('完成--执行用户V3数据上传队列失败任务弥补定时任务'.date('Y-m-d H:i:s'));
    }
}
