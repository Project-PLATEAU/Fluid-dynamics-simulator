<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use App\Traits\HasCompositePrimaryKeyTrait;
use Faker\Core\Uuid;

/**
 * Class SimulationModelPolicy
 *
 * @property Uuid $simulation_model_id
 * @property int $stl_type_id
 * @property int $policy_id
 *
 * @property SimulationModel $simulation_model
 * @property StlType $stl_type
 * @property Policy $policy
 *
 * @package App\Models
 */
class SimulationModelPolicy extends DbModel
{
    // LaravelのORMでは複合PKは推奨されておらず、保存できないエラーの対策
    // 参考：https://qiita.com/derasado/items/ff692411ef50f222af32
    use HasCompositePrimaryKeyTrait;
	protected $table = 'simulation_model_policy';
    protected $primaryKey = ['simulation_model_id', 'stl_type_id', 'policy_id'];
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		// 'simulation_model_id' => 'uuid',
		'stl_type_id' => 'int',
		'policy_id' => 'int'
	];

	protected $fillable = [
        'simulation_model_id',
        'stl_type_id',
		'policy_id'
	];

	public function simulation_model()
	{
		return $this->belongsTo(SimulationModel::class, 'simulation_model_id');
	}

	public function stl_type()
	{
		return $this->belongsTo(StlType::class, 'stl_type_id');
	}

	public function policy()
	{
		return $this->belongsTo(Policy::class, 'policy_id');
	}
}
