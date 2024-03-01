<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use App\Traits\HasCompositePrimaryKeyTrait;
use Faker\Core\Uuid;

/**
 * Class SolarAbsorptivity
 *
 * @property Uuid $simulation_model_id
 * @property int $stl_type_id
 * @property float|null $solar_absorptivity
 * @property float|null $heat_removal
 *
 * @property SimulationModel $simulation_model
 * @property StlType $stl_type
 *
 * @package App\Models
 */
class SolarAbsorptivity extends DbModel
{
    // LaravelのORMでは複合PKは推奨されておらず、保存できないエラーの対策
    // 参考：https://qiita.com/derasado/items/ff692411ef50f222af32
    use HasCompositePrimaryKeyTrait;

	protected $table = 'solar_absorptivity';
    protected $primaryKey = ['simulation_model_id', 'stl_type_id'];
    public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'stl_type_id' => 'int',
		'solar_absorptivity' => 'float',
        'heat_removal' => 'float'
	];

	protected $fillable = [
        'simulation_model_id',
        'stl_type_id',
		'solar_absorptivity',
        'heat_removal'
	];

	public function simulation_model()
	{
		return $this->belongsTo(SimulationModel::class, 'simulation_model_id');
	}

	public function stl_type()
	{
		return $this->belongsTo(StlType::class, 'stl_type_id');
	}

    /**
     * シミュレーションモデルIDとSTLファイル種別IDで日射吸収率のレコードを取得
     * @param Uuid $simulation_model_id シミュレーションモデルID
     * @param integer $stl_type_id STLファイル種別ID
     *
     * @return self
     */
    public static function getBySimulationIdAndStlTypeId($simulation_model_id, $stl_type_id)
    {
        return self::where(['simulation_model_id' => $simulation_model_id, 'stl_type_id' => $stl_type_id])->first();
    }

    /**
     * シミュレーションモデルIDで日射吸収率のレコードを取得
     * @param Uuid $simulation_model_id シミュレーションモデルID
     *
     * @return self
     */
    public static function getBySimulationId($simulation_model_id)
    {
        return self::where(['simulation_model_id' => $simulation_model_id])->get();
    }

}
