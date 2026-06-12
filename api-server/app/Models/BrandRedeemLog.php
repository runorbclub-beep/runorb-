<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class BrandRedeemLog
 * @package App\Models
 * User: zxw
 * Date: 2021/9/15 13:58
 * @method static where(array[] $array)
 * @method static create($map)
 */
class BrandRedeemLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_no', 'type', 'brand_id', 'brand_shop_id', 'brand_user_id', 'operate_user_id', 'user_id', 'integral', 'project_name', 'change_code', 'integral_bill_type', 'integral_balance', 'remark', 'created_at', 'updated_at'];

    /**
     * 隐藏字段
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 账单流水用户ID与用户ID 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/9/22 14:34
     */
    public function usr_user(): HasOne
    {
        return $this->hasOne(UsrUser::class, 'user_id', 'user_id');
    }

    /**
     * 积分账单流水分店ID与品牌分店ID 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/9/22 14:32
     */
    public function brand_shop(): HasOne
    {
        return $this->hasOne(BrandShop::class, 'id', 'brand_shop_id');
    }

    /**
     * 积分账单流水与品牌分店店员id（非关联的用户id）
     * @return HasOne
     * User: zxw
     * Date: 2021/9/24 10:06
     */
    public function brand_users(): HasOne
    {
        return $this->hasOne(BrandUser::class,'id','brand_user_id');
    }

}
