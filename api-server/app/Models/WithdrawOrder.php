<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class WithdrawOrder extends Model
{
    use HasFactory;

    protected $table = 'withdraw_orders';
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'user_id', 'account', 'pay_channel_id', 'actual_name', 'user_name', 'order_no', 'order_num', 'pay_amount','order_time', 'pay_order_no', 'pay_fund_order_id', 'pay_time', 'status', 'remark', 'msg_info', 'error_info', 'integral', 'integral_exchange_ratio'];

    /**
     * 隐藏字段
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_time' => 'datetime:Y-m-d H:i:s',
        'pay_time' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
