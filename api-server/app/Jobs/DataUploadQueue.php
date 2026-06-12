<?php

namespace App\Jobs;

use App\Exceptions\BusinessException;
use App\Services\LocalPlayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * V3上传接口队列
 */
class DataUploadQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws BusinessException
     */
    public function handle()
    {
        Log::info("开始进入===消费队列 ".$this->data->post_play_id.":用户 ".$this->data->user_id." 进入队列上传了，运动数据 ".$this->data->user_play_id." 时间：".date('Y-m-d H:i:s'));
        //echo "开始进入===消费队列 ".$this->data->post_play_id.":用户 ".$this->data->user_id." 进入队列上传了，运动数据 ".$this->data->user_play_id." 时间：".date('Y-m-d H:i:s');

        $localPlayService = new LocalPlayService();
        $handle = $localPlayService->handlePlayLog($this->data);
        if ($handle == false){
            Log::info("结束进入===消费队列 ".$this->data->post_play_id.":用户 ".$this->data->user_id." 进入队列上传了，运动数据 ".$this->data->user_play_id." 时间：".date('Y-m-d H:i:s')."====失败");
            //echo "结束进入===消费队列 ".$this->data->post_play_id.":用户 ".$this->data->user_id." 进入队列上传了，运动数据 ".$this->data->user_play_id." 时间：".date('Y-m-d H:i:s')."====失败";
        }else{
            Log::info("结束进入===消费队列 ".$this->data->post_play_id.":用户 ".$this->data->user_id." 进入队列上传了，运动数据 ".$this->data->user_play_id." 时间：".date('Y-m-d H:i:s')."====成功");
            //echo "结束进入===消费队列 ".$this->data->post_play_id.":用户 ".$this->data->user_id." 进入队列上传了，运动数据 ".$this->data->user_play_id." 时间：".date('Y-m-d H:i:s')."====成功";
        }

    }
}
