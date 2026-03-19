<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;

/**
 * Class TreeType
 *
 * @property int $tree_type_id
 * @property string|null $tree_type_name
 * @property float|null $canopy_diameter
 * @property float|null $height
 *
 * @package App\Models
 */
class TreeType extends DbModel
{
	protected $table = 'tree_type';
	protected $primaryKey = 'tree_type_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'tree_type_id' => 'int',
		'canopy_diameter' => 'float',
		'height' => 'float'
	];

	protected $fillable = [
		'tree_type_name',
		'canopy_diameter',
		'height'
	];
}
