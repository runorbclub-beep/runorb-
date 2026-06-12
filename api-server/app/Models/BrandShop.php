<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BrandShop
 * @package App\Models
 * User: zxw
 * Date: 2021/9/15 14:02
 * @method static whereColumn(string $string, string $string1)
 * @method static where(string $string, mixed $id)
 * @method static create($param)
 */
class BrandShop extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['brand_id', 'shop_name', 'shop_address', 'shop_integral', 'created_at', 'updated_at'];

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

}
