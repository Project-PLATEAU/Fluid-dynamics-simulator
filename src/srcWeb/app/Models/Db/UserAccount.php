<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAccount
 *
 * @property string $user_id
 * @property string|null $password
 * @property string|null $display_name
 * @property string|null $note
 * @property Carbon|null $last_update_datetime
 *
 * @property Collection|CityModel[] $city_models
 * @property Collection|CityModelReferenceAuthority[] $city_model_reference_authorities
 *
 * @package App\Models
 */
class UserAccount extends DbModel
{
	protected $table = 'user_account';
	protected $primaryKey = 'user_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'last_update_datetime' => 'datetime'
	];

	protected $hidden = [
		'password'
	];

	protected $fillable = [
        'user_id',
		'password',
		'display_name',
		'note',
		'last_update_datetime'
	];

	public function city_models()
	{
		return $this->hasMany(CityModel::class, 'registered_user_id');
	}

	public function city_model_reference_authorities()
	{
		return $this->hasMany(CityModelReferenceAuthority::class, 'user_id');
	}
}
