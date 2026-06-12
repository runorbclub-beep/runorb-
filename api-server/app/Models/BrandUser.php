<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class BrandUser
 * @package App\Models
 * User: zxw
 * Date: 2021/9/15 14:02
 * @method static where(array[] $array)
 * @method static create($param)
 */
class BrandUser extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['brand_id', 'brand_shop_id', 'user_id', 'real_name', 'nickname', 'phone', 'email', 'user_img', 'created_at'];

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
     * 品牌表与品牌店员 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/9/22 16:25
     */
    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class,'id','brand_id');
    }

    /**
     * 品牌分店表与品牌店员 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/9/22 16:31
     */
    public function brand_shop(): HasOne
    {
        return $this->hasOne(BrandShop::class,'id','brand_shop_id');
    }

}
