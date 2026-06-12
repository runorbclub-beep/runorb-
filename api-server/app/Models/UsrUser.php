<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class UsrUser
 *
 * @property string $user_id
 * @property string|null $sys_user_type_id
 * @property string|null $sys_sex_id
 * @property string|null $sys_country_id
 * @property int|null $created_time
 * @property int|null $updated_time
 * @property string|null $created_uid
 * @property string|null $updated_uid
 * @property int|null $status
 * @property string|null $user_name
 * @property string|null $role_name
 * @property string|null $role_code
 * @property string|null $email
 * @property string|null $password
 * @property string|null $user_img
 * @property string|null $access_token
 * @property int|null $exp_time
 * @property int|null $user_name_change
 * @property int|null $user_img_change
 * @property string|null $birthday
 * @property int|null $user_height
 * @property float|null $user_weight
 * @property string|null $id_card
 * @property string|null $self_description
 * @property string|null $device_uid
 * @property string|null $open_weixin_id
 * @property string|null $open_qq_id
 * @property string|null $open_weibo_id
 * @property string|null $open_alipay_id
 * @property string|null $open_ios_id
 * @property string|null $open_twitter_id
 * @property string|null $open_facebook_id
 * @property string|null $phone
 * @property string|null $address
 * @property array|null $address_json
 * @property string|null $phone_prefix
 * @property string|null $live_platform
 * @property string|null $live_id
 * @property string|null $wechart_id
 * @property int|null $is_members
 * @property int|null $members_exptime
 * @property int|null $members_status
 * @property int|null $members_join_time
 * @property string|null $address_detail
 * @property int|null $is_yang
 * @property string|null $share_code
 * @property string|null $share_user_id
 * @property string|null $real_name
 * @property int|null $user_city_change
 * @property int|null $user_birthday_change
 *
 * @property SysCountry|null $sys_country
 * @property SysSex|null $sys_sex
 * @property SysUserType|null $sys_user_type
 * @property Collection|MatchUserStar[] $match_user_stars
 * @property Collection|MatchsUser[] $matchs_users
 * @property Collection|MatchsUserGrade[] $matchs_user_grades
 * @property Collection|MatchsUserIntegral[] $matchs_user_integrals
 * @property Collection|PkRoom[] $pk_rooms
 * @property Collection|SysDayList[] $sys_day_lists
 * @property Collection|SysMessage[] $sys_messages
 * @property Collection|SysMonthList[] $sys_month_lists
 * @property Collection|SysSeasonList[] $sys_season_lists
 * @property Collection|SysWeekList[] $sys_week_lists
 * @property Collection|SysYearList[] $sys_year_lists
 * @property Collection|UserAchievement[] $user_achievements
 * @property Collection|UserDevice[] $user_devices
 * @property Collection|UserFocus[] $user_foci
 * @property Collection|UserGradeYearMonth[] $user_grade_year_months
 * @property Collection|UserGradetWeek[] $user_gradet_weeks
 * @property Collection|UserGroupAssociated[] $user_group_associateds
 * @property Collection|UserLevelAssociated[] $user_level_associateds
 * @property Collection|UserMedalAssociated[] $user_medal_associateds
 * @property Collection|UserPkList[] $user_pk_lists
 * @property Collection|UserPlay[] $user_plays
 * @property Collection|UserPlayProgress[] $user_play_progresses
 *
 * @package App\Models
 * @method static where(string $string, mixed $phone)
 * @method static select(string $string, string $string1)
 */
class UsrUser extends Model
{
	protected $table = 'usr_user';
	protected $primaryKey = 'user_id';
	public $incrementing = false;

    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';

    /**
     * 这个时间字段被为被格式化为UNIX 时间戳的形式存储
     *
     * @var string
     */
    protected $dateFormat = 'U';

	protected $casts = [
		'created_time' => 'int',
		'updated_time' => 'int',
		'status' => 'int',
		'exp_time' => 'int',
		'user_name_change' => 'int',
		'user_img_change' => 'int',
		'user_height' => 'int',
		'user_weight' => 'float',
		'address_json' => 'json',
		'is_members' => 'int',
		'members_exptime' => 'int',
		'members_status' => 'int',
		'members_join_time' => 'int',
		'is_yang' => 'int',
		'user_city_change' => 'int',
		'user_birthday_change' => 'int',
        'is_group' => 'int',
        'integral' => 'float',
        'version' => 'string',
        'channel' => 'string',
        'device_model' => 'string',
	];

	protected $hidden = [
		'password',
		'access_token'
	];

	protected $fillable = [
		'user_id',
		'sys_user_type_id',
		'sys_sex_id',
		'sys_country_id',
		'created_time',
		'updated_time',
		'created_uid',
		'updated_uid',
		'status',
		'user_name',
		'role_name',
		'role_code',
		'email',
		'password',
		'user_img',
		'access_token',
		'exp_time',
		'user_name_change',
		'user_img_change',
		'birthday',
		'user_height',
		'user_weight',
		'id_card',
		'self_description',
		'device_uid',
		'open_weixin_id',
		'open_qq_id',
		'open_weibo_id',
		'open_alipay_id',
		'open_ios_id',
		'open_twitter_id',
		'open_facebook_id',
		'phone',
		'address',
		'address_json',
		'phone_prefix',
		'live_platform',
		'live_id',
		'wechart_id',
		'is_members',
		'members_exptime',
		'members_status',
		'members_join_time',
		'address_detail',
		'is_yang',
		'share_code',
		'share_user_id',
		'real_name',
		'user_city_change',
		'user_birthday_change',
        'is_group',
        'integral',
        'version',
        'channel',
        'device_model',
        'photo_text',
        'third_info'
	];

	public function sys_country()
	{
		return $this->belongsTo(SysCountry::class);
	}

	public function sys_sex()
	{
		return $this->belongsTo(SysSex::class);
	}

	public function sys_user_type()
	{
		return $this->belongsTo(SysUserType::class);
	}

	public function match_user_stars()
	{
		return $this->hasMany(MatchUserStar::class, 'user_id');
	}

	public function matchs_users()
	{
		return $this->hasMany(MatchsUser::class, 'user_id');
	}

	public function matchs_user_grades()
	{
		return $this->hasMany(MatchsUserGrade::class, 'user_id');
	}

	public function matchs_user_integrals()
	{
		return $this->hasMany(MatchsUserIntegral::class, 'user_id');
	}

	public function pk_rooms()
	{
		return $this->hasMany(PkRoom::class, 'user_id');
	}

	public function sys_day_lists()
	{
		return $this->hasMany(SysDayList::class, 'user_id');
	}

	public function sys_messages()
	{
		return $this->hasMany(SysMessage::class, 'user_id');
	}

	public function sys_month_lists()
	{
		return $this->hasMany(SysMonthList::class, 'user_id');
	}

	public function sys_season_lists()
	{
		return $this->hasMany(SysSeasonList::class, 'user_id');
	}

	public function sys_week_lists()
	{
		return $this->hasMany(SysWeekList::class, 'user_id');
	}

	public function sys_year_lists()
	{
		return $this->hasMany(SysYearList::class, 'user_id');
	}

	public function user_achievements()
	{
		return $this->hasMany(UserAchievement::class, 'user_id');
	}

	public function user_devices()
	{
		return $this->hasMany(UserDevice::class, 'user_id');
	}

	public function user_foci()
	{
		return $this->hasMany(UserFocus::class);
	}

	public function user_grade_year_months()
	{
		return $this->hasMany(UserGradeYearMonth::class, 'user_id');
	}

	public function user_gradet_weeks()
	{
		return $this->hasMany(UserGradetWeek::class, 'user_id');
	}

	public function user_group_associateds()
	{
		return $this->hasMany(UserGroupAssociated::class, 'user_id');
	}

	public function user_level_associateds()
	{
		return $this->hasMany(UserLevelAssociated::class, 'user_id');
	}

	public function user_medal_associateds()
	{
		return $this->hasMany(UserMedalAssociated::class, 'user_id');
	}

	public function user_pk_lists()
	{
		return $this->hasMany(UserPkList::class, 'user_id');
	}

	public function user_plays()
	{
		return $this->hasMany(UserPlay::class, 'user_id');
	}

	public function user_play_progresses()
	{
		return $this->hasMany(UserPlayProgress::class, 'user_id');
	}

    /**
     * 用户对用户成就 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/10/23 14:44
     */
	public function user_achievement_one(): HasOne
    {
        return $this->hasOne(UserAchievement::class,'user_id','user_id');
    }

    /**
     * 企业与用户表 一对一（带企业where条件） 摇才保险 120234611644043264  聚星 84060776356122624
     * @return HasOne
     * User: zxw
     * Date: 2021/11/24 16:37
     */
    public function qiye_shake_user(): HasOne
    {
        return $this->hasOne(QiyeShakeUser::class,'user_id','user_id')->where('sys_qiye_shake_id',120234611644043264);
    }

    /**
     * 用户表与战队成员表 一对一
     * @return HasOne
     * User: zxw
     * Date: 2021/12/10 16:44
     */
    public function user_clan_members(): HasOne
    {
        return $this->hasOne(UserClanMember::class,'user_id','user_id');
    }
}
