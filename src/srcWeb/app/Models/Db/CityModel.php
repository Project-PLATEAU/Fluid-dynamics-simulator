<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use App\Services\UserAccountService;
use Carbon\Carbon;
use Faker\Core\Uuid;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class CityModel
 *
 * @property Uuid $city_model_id
 * @property string|null $identification_name
 * @property string $registered_user_id
 * @property Carbon|null $last_update_datetime
 * @property bool|null $preset_flag
 * @property string|null $url
 *
 * @property UserAccount $user_account
 * @property Collection|CityModelReferenceAuthority[] $city_model_reference_authorities
 * @property Collection|Region[] $regions
 * @property Collection|SimulationModel[] $simulation_models
 *
 * @package App\Models
 */
class CityModel extends DbModel
{
    /**
     * モデルに関連付けるテーブル
     *
     * @var string
     */
	protected $table = 'city_model';

    /**
     * テーブルに関連付ける主キー
     *
     * @var string
     */
	protected $primaryKey = 'city_model_id';

    /**
     * 主キーのデータ型がuuidのため、主キータイプをstringに変更する必要がある。(デフォルト：int)
     */
    protected $keyType = 'string';

    /**
     * モデルのタイムスタンプを更新するかの指示
     *
     * @var bool
     */
	public $timestamps = false;

	protected $casts = [
		'last_update_datetime' => 'datetime',
		'preset_flag' => 'bool'
	];

    /**
     * 複数代入する属性
     *
     * @var array
     */
	protected $fillable = [
		'identification_name',
		'registered_user_id',
		'last_update_datetime',
		'preset_flag',
        'url'
	];

    /**
     * ユーザアカウントとのリレーション設定
     * @return
     */
	public function user_account()
	{
		return $this->belongsTo(UserAccount::class, 'registered_user_id');
	}

    /**
     * 都市モデル参照権限とのリレーション設定
     * @return
     */
	public function city_model_reference_authoritys()
	{
		return $this->hasMany(CityModelReferenceAuthority::class, 'city_model_id');
	}

    /**
     * 解析対象地域とのリレーション設定
     * @return
     */
	public function regions()
	{
		return $this->hasMany(Region::class, 'city_model_id')->orderBy('region_id', 'asc');;
	}

    /**
     * シミュレーションモデルとのリレーション設定
     * @return
     */
    public function simulation_models()
    {
        return $this->hasMany(SimulationModel::class, 'city_model_id');
    }
}
