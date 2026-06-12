<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Brand
 * @package App\Models
 * User: zxw
 * Date: 2021/9/15 13:58
 * @method static paginate(int $int)
 * @method static create($param)
 * @method static withCount(string $string)
 * @method static select(string $select)
 * @method static where(string $string, mixed $id)
 */
class Brand extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * 可批量赋值属性
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['logo', 'brand_name', 'contact_person', 'phone', 'created_at'];

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
     * 品牌与店铺 一对多
     * @return HasMany
     * User: zxw
     * Date: 2021/9/15 14:53
     */
    public function brandShop(): HasMany
    {
        return $this->hasMany(\App\Models\BrandShop::class,'brand_id');
    }

}
