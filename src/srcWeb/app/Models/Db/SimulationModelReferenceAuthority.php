<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Carbon\Carbon;
use Faker\Core\Uuid;

/**
 * Class SimulationModelReferenceAuthority
 *
 * @property Uuid $simulation_model_id
 * @property string $user_id
 * @property Carbon|null $last_update_datetime
 *
 * @property SimulationModel $simulation_model
 * @property UserAccount $user_account
 *
 * @package App\Models
 */
class SimulationModelReferenceAuthority extends DbModel
{
	protected $table = 'simulation_model_reference_authority';
	protected $primaryKey = ['simulation_model_id', 'user_id'];
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'last_update_datetime' => 'datetime'
	];

	protected $fillable = [
        'simulation_model_id',
		'user_id',
		'last_update_datetime'
	];

	public function simulation_model()
	{
		return $this->belongsTo(SimulationModel::class);
	}

	public function user_account()
	{
		return $this->belongsTo(UserAccount::class, 'user_id');
	}
}
