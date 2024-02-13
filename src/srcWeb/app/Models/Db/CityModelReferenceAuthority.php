<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Carbon\Carbon;
use Faker\Core\Uuid;

/**
 * Class CityModelReferenceAuthority
 *
 * @property Uuid $city_model_id
 * @property string $user_id
 * @property Carbon|null $registered_datetime
 *
 * @property CityModel $city_model
 * @property UserAccount $user_account
 *
 * @package App\Models
 */
class CityModelReferenceAuthority extends DbModel
{
    /**
     * モデルに関連付けるテーブル
     *
     * @var string
     */
	protected $table = 'city_model_reference_authority';

    /**
     * テーブルに関連付ける主キー
     *
     * @var string
     */
	protected $primaryKey = ['city_model_id', 'user_id'];

    /**
     * モデルのIDを自動増分するか
     *
     * @var bool
     */
	public $incrementing = false;

    /**
     * モデルのタイムスタンプを更新するかの指示
     *
     * @var bool
     */
	public $timestamps = false;

	protected $casts = [
		'registered_datetime' => 'datetime'
	];

    /**
     * 複数代入する属性
     *
     * @var array
     */
	protected $fillable = [
        'city_model_id',
		'user_id',
		'registered_datetime'
	];

    /**
     * 都市モデルとのリレーション設定
     * @return
     */
	public function city_model()
	{
		return $this->belongsTo(CityModel::class);
	}

    /**
     *ユーザアカウントとのリレーション設定
     * @return
     */
	public function user_account()
	{
		return $this->belongsTo(UserAccount::class, 'user_id');
	}
}
