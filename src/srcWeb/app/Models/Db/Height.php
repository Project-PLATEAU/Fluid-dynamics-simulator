<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Height
 *
 * @property int $height_id
 * @property float|null $height
 *
 * @property Collection|Visualization[] $visualizations
 *
 * @package App\Models
 */
class Height extends DbModel
{
	protected $table = 'height';
	protected $primaryKey = 'height_id';
	public $timestamps = false;

	protected $casts = [
        'height_id' => 'int',
		'height' => 'float'
	];

	protected $fillable = [
        'height_id',
		'height'
	];

	public function visualizations()
	{
		return $this->hasMany(Visualization::class);
	}
}
