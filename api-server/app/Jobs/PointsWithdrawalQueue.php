<?php

namespace App\Jobs;

use App\Exceptions\BusinessException;
use App\Services\AlipayEasyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 提现支付队列
 */
class PointsWithdrawalQueue implements ShouldQueue
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
        $alipayEasyService = new AlipayEasyService();
        $alipayWithdrawal = $alipayEasyService->alipayWithdrawal($this->data);
        if ($alipayWithdrawal == true){
            Log::info('用户'.$this->data['user_id'].$this->data['actual_name'].'金额:'.$this->data['pay_amount'].'队列提现 ======= 成功');
        }else{
            Log::error('用户'.$this->data['user_id'].$this->data['actual_name'].'金额:'.$this->data['pay_amount'].'队列提现 ======= 失败');
        }

    }
}
