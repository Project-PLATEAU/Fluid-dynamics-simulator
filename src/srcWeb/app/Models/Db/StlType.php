<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class StlType
 *
 * @property int $stl_type_id
 * @property string|null $stl_type_name
 * @property bool|null $required_flag
 * @property bool|null $ground_flag
 *
 * @property Collection|SolarAbsorptivity[] $solar_absorptivities
 * @property Collection|StlModel[] $stl_models
 *
 * @package App\Models
 */
class StlType extends DbModel
{
	protected $table = 'stl_type';
	protected $primaryKey = 'stl_type_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'stl_type_id' => 'int',
		'required_flag' => 'bool',
        'ground_flag' => 'bool'
	];

	protected $fillable = [
        'stl_type_id',
		'stl_type_name',
		'required_flag',
        'ground_flag'
	];

    public function solar_absorptivities()
    {
        return $this->hasMany(SolarAbsorptivity::class, 'stl_type_id');
    }

	public function stl_models()
	{
		return $this->hasMany(StlModel::class, 'stl_type_id');
	}
}
