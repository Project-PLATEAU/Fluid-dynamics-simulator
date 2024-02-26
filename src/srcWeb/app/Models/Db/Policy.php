<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Policy
 *
 * @property int $policy_id
 * @property string|null $policy_name
 * @property float|null $solar_absorptivity
 * @property float|null $heat_removal
 *
 * @property Collection|SimulationModel[] $simulation_models
 *
 * @package App\Models
 */
class Policy extends DbModel
{
	protected $table = 'policy';
	protected $primaryKey = 'policy_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'policy_id' => 'int',
		'solar_absorptivity' => 'float',
		'heat_removal' => 'float'
	];

	protected $fillable = [
        'policy_id',
		'policy_name',
		'solar_absorptivity',
		'heat_removal'
	];

	public function simulation_models()
	{
		return $this->belongsToMany(SimulationModel::class, 'simulation_model_policy')
					->withPivot('stl_type_id');
	}
}
