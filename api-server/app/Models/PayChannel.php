<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayChannel extends Model
{
    use HasFactory;

    protected $table = 'pay_channels';
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'pay_code', 'pay_name', 'pay_logo', 'status', 'zfb_appid', 'zfb_alipay_user_id', 'zfb_merchant_private_key', '_zfb_notify_url', 'zfb_encrypt_key'];

    /**
     * 隐藏字段
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'status', 'zfb_appid', 'zfb_alipay_user_id', 'zfb_merchant_private_key', '_zfb_notify_url', 'zfb_encrypt_key'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * 平台支付渠道与用户支付账户 一对一
     * @return HasOne
     * User: zxw
     * Date: 2022/1/24 10:43
     */
    public function user_pay_account(): HasOne
    {
        return $this->hasOne(UserPayAccount::class,'pay_channel_id','id');
    }
}
