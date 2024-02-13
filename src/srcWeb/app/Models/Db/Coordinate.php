<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Coordinate
 *
 * @property int $coordinate_id
 * @property string|null $coordinate_name
 * @property float|null $origin_latitude
 * @property float|null $origin_longitude
 *
 * @property Collection|Region[] $regions
 *
 * @package App\Models
 */
class Coordinate extends DbModel
{
	protected $table = 'coordinate';
	protected $primaryKey = 'coordinate_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'coordinate_id' => 'int',
		'origin_latitude' => 'float',
		'origin_longitude' => 'float'
	];

	protected $fillable = [
        'coordinate_id',
		'coordinate_name',
		'origin_latitude',
		'origin_longitude'
	];

	public function regions()
	{
		return $this->hasMany(Region::class);
	}
}
