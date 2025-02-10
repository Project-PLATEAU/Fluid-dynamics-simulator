<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Faker\Core\Uuid;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Region
 *
 * @property Uuid $region_id
 * @property Uuid $city_model_id
 * @property string|null $region_name
 * @property string $user_id
 * @property int $coordinate_id
 * @property float|null $south_latitude
 * @property float|null $north_latitude
 * @property float|null $west_longitude
 * @property float|null $east_longitude
 * @property float|null $ground_altitude
 * @property float|null $sky_altitude
 *
 * @property CityModel $city_model
 * @property Coordinate $coordinate
 * @property Collection|StlModel[] $stl_models
 * @property Collection|SimulationModel[] $simulation_models
 *
 * @package App\Models
 */
class Region extends DbModel
{
	protected $table = 'region';
	protected $primaryKey = 'region_id';
    /**
     * 主キーのデータ型がuuidのため、主キータイプをstringに変更する必要がある。(デフォルト：int)
     */
    protected $keyType = 'string';
	public $timestamps = false;

	protected $casts = [
		'coordinate_id' => 'int',
		// 'south_latitude' => 'float',
		// 'north_latitude' => 'float',
		// 'west_longitude' => 'float',
		// 'east_longitude' => 'float',
		// 'ground_altitude' => 'float',
		// 'sky_altitude' => 'float'
	];

	protected $fillable = [
		'city_model_id',
		'region_name',
        'user_id',
		'coordinate_id',
		'south_latitude',
		'north_latitude',
		'west_longitude',
		'east_longitude',
		'ground_altitude',
		'sky_altitude'
	];

	public function city_model()
	{
		return $this->belongsTo(CityModel::class);
	}

	public function coordinate()
	{
		return $this->belongsTo(Coordinate::class);
	}

    public function user_account()
    {
        return $this->belongsTo(UserAccount::class, 'user_id');
    }

	public function stl_models()
	{
		return $this->hasMany(StlModel::class, 'region_id')->orderBy('region_id', 'asc');;
	}

	public function simulation_models()
	{
		return $this->hasMany(SimulationModel::class, 'region_id');
	}
}
