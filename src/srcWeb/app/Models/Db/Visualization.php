<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;

/**
 * Class Visualization
 *
 * @property int $simulation_model_id
 * @property int $visualization_type
 * @property int $height_id
 * @property int $legend_type
 * @property string|null $visualization_file
 * @property string|null $geojson_file
 * @property string|null $legend_label_higher
 * @property string|null $legend_label_lower
 *
 * @property SimulationModel $simulation_model
 * @property Height $height
 *
 * @package App\Models
 */
class Visualization extends DbModel
{
	protected $table = 'visualization';
    protected $primaryKey = ['simulation_model_id', 'visualization_type', 'height_id', 'legend_type'];
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'simulation_model_id' => 'int',
		'visualization_type' => 'int',
		'height_id' => 'int',
        'legend_type' => 'int'
	];

	protected $fillable = [
        'simulation_model_id',
        'visualization_type',
        'height_id',
        'legend_type',
		'visualization_file',
		'geojson_file',
        'legend_label_higher',
        'legend_label_lower'
	];

	public function simulation_model()
	{
		return $this->belongsTo(SimulationModel::class);
	}

	public function height()
	{
		return $this->belongsTo(Height::class);
	}
}
