<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Commons\Constants;
use App\Models\DbModel;
use Carbon\Carbon;
use Faker\Core\Uuid;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class SimulationModel
 *
 * @property Uuid $simulation_model_id
 * @property string|null $identification_name
 * @property Uuid $city_model_id
 * @property Uuid $region_id
 * @property string $registered_user_id
 * @property Carbon|null $last_update_datetime
 * @property bool|null $preset_flag
 * @property float|null $temperature
 * @property float|null $wind_speed
 * @property int|null $wind_direction
 * @property float|null $humidity
 * @property Carbon|null $solar_altitude_date
 * @property int|null $solar_altitude_time
 * @property float|null $south_latitude
 * @property float|null $north_latitude
 * @property float|null $west_longitude
 * @property float|null $east_longitude
 * @property float|null $ground_altitude
 * @property float|null $sky_altitude
 * @property Uuid $solver_id
 * @property int|null $mesh_level
 * @property int|null $run_status
 * @property string|null $run_status_details
 * @property string|null $cfd_error_log_file
 * @property Carbon|null $last_sim_start_datetime
 * @property Carbon|null $last_sim_end_datetime
 * @property bool|null $disclosure_flag
 *
 * @property CityModel $city_model
 * @property Region $region
 * @property UserAccount $user_account
 * @property Solver $solver
 * @property Collection|Visualization[] $visualizations
 * @property Collection|SolarAbsorptivity[] $solar_absorptivities
 * @property Collection|SimulationModelReferenceAuthority[] $simulation_model_reference_authorities
 *
 * @package App\Models
 */
class SimulationModel extends DbModel
{
	protected $table = 'simulation_model';
	protected $primaryKey = 'simulation_model_id';
    /**
     * 主キーのデータ型がuuidのため、主キータイプをstringに変更する必要がある。(デフォルト：int)
     */
    protected $keyType = 'string';
	public $timestamps = false;

	protected $casts = [
		'last_update_datetime' => 'datetime',
		'preset_flag' => 'bool',
		'temperature' => 'float',
		'wind_speed' => 'float',
		'wind_direction' => 'int',
        'humidity' => 'float',
		'solar_altitude_date' => 'datetime',
		'solar_altitude_time' => 'int',
		// 'south_latitude' => 'float',
		// 'north_latitude' => 'float',
		// 'west_longitude' => 'float',
		// 'east_longitude' => 'float',
		// 'ground_altitude' => 'float',
		// 'sky_altitude' => 'float',
		'mesh_level' => 'int',
		'solar_absorption_rate_building_1' => 'float',
		'solar_absorption_rate_building_2' => 'float',
		'solar_absorption_rate_building_3' => 'float',
		'solar_absorption_rate_ground_1' => 'float',
		'solar_absorption_rate_ground_2' => 'float',
		'solar_absorption_rate_ground_3' => 'float',
		'run_status' => 'int',
		'last_sim_start_datetime' => 'datetime',
		'last_sim_end_datetime' => 'datetime',
        'disclosure_flag' => 'bool'
	];

	protected $fillable = [
		'identification_name',
		'city_model_id',
		'region_id',
		'registered_user_id',
		'last_update_datetime',
		'preset_flag',
		'temperature',
		'wind_speed',
		'wind_direction',
        'humidity',
		'solar_altitude_date',
		'solar_altitude_time',
		'south_latitude',
		'north_latitude',
		'west_longitude',
		'east_longitude',
		'ground_altitude',
		'sky_altitude',
		'solver_id',
		'mesh_level',
		'run_status',
		'run_status_details',
		'cfd_error_log_file',
		'last_sim_start_datetime',
		'last_sim_end_datetime',
        'disclosure_flag'
	];

	public function city_model()
	{
		return $this->belongsTo(CityModel::class, 'city_model_id');
	}

	public function region()
	{
		return $this->belongsTo(Region::class, 'region_id');
	}

	public function user_account()
	{
		return $this->belongsTo(UserAccount::class, 'registered_user_id');
	}

	public function solver()
	{
		return $this->belongsTo(Solver::class, 'solver_id');
	}

    public function policies()
    {
        return $this->belongsToMany(Policy::class, 'simulation_model_policy')
        ->withPivot('stl_type_id');
    }

    public function simulation_model_policies()
    {
        return $this->hasMany(SimulationModelPolicy::class, 'simulation_model_id');
    }

    public function simulation_model_reference_authorities()
	{
		return $this->hasMany(SimulationModelReferenceAuthority::class, 'simulation_model_id');
	}

    public function solar_absorptivities()
    {
        return $this->hasMany(SolarAbsorptivity::class, 'simulation_model_id');
    }

    public function visualizations()
    {
        return $this->hasMany(Visualization::class, 'simulation_model_id');
    }

    /**
     *
     * 実行ステータスにより、表データセル要素の背景色を設定
     * @return string 背景色(※bootstrapのクラス)
     */
    public function setTableTdColorByRunStatus()
    {

        if ($this->run_status == 1 || $this->run_status == 2) {
            return "table-primary";
        } else if ($this->run_status == 3) {
            return "table-success";
        } else if ($this->run_status == 4) {
            return "table-danger";
        }else if ($this->run_status == 5 || $this->run_status == 6 || $this->run_status == 7) {
            return "table-secondary";
        } else {
            return "";
        }
    }

    /**
     *
     * 実行ステータス名を取得
     * @return string 実行ステータス名
     */
    public function getRunStatusName()
    {
        if ($this->run_status == 0) {
            return Constants::RUN_STATUS_NONE;
        } else if ($this->run_status == 1) {
            return Constants::RUN_STATUS_START_PROCESSING;
        } else if ($this->run_status == 2) {
            return Constants::RUN_STATUS_RUNNING;
        } else if ($this->run_status == 3) {
            return Constants::RUN_STATUS_NORMAL_END;
        } else if ($this->run_status == 4) {
            return Constants::RUN_STATUS_ABNORMALITY_END;
        } else if ($this->run_status == 5) {
            return Constants::RUN_STATUS_CANCEL_PROCESSING;
        } else if ($this->run_status == 6) {
            return Constants::RUN_STATUS_CANCEL;
        } else if ($this->run_status == 7) {
            return Constants::RUN_STATUS_ADMIN_CANCEL;
        } else {
            return Constants::RUN_STATUS_NONE;
        }
    }

    /**
     * 公開状況を取得
     * @return string 公開状況
     */
    public function getPublishStatus()
    {
        // SM26 一般公開フラグが有効であれば「公開中」、無効であれば「未」と表示する
        if ($this->disclosure_flag) {
            return Constants::SIMULATION_MODEL_PUBLISH;
        } else {
            return Constants::SIMULATION_MODEL_NON_PUBLISH;
        }
    }

    /**
     * 東西南北境界指定のデフォルトを判定する。
     * @return bool
     *  trueの場合、 全域とする。
     *  falseの場合、狭域とする。
     */
    public function isWholeArea()
    {
        // 以下、いずれかを満たさない場合には狭域指定とします。
        //   ・CA5南端緯度とSM13南端緯度が同一
        //   ・CA6北端緯度とSM14北端緯度が同一
        //   ・CA7西端経度とSM15西端経度が同一
        //   ・CA8東端経度とSM16東端経度が同一
        if (($this->region->south_latitude == $this->south_latitude)
            && ($this->region->north_latitude == $this->north_latitude)
            && ($this->region->west_longitude == $this->west_longitude)
            && ($this->region->east_longitude == $this->east_longitude)) {
                return true;
        } else {
            return false;
        }
    }
}
