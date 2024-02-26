<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;
use App\Traits\HasCompositePrimaryKeyTrait;
use Carbon\Carbon;
use Faker\Core\Uuid;

/**
 * Class StlModel
 *
 * @property Uuid $region_id
 * @property int $stl_type_id
 * @property string|null $stl_file
 * @property Carbon|null $upload_datetime
 * @property float|null $solar_absorptivity
 * @property float|null $heat_removal
 *
 * @property Region $region
 * @property StlType $stl_type
 *
 * @package App\Models
 */
class StlModel extends DbModel
{

    // LaravelのORMでは複合PKは推奨されておらず、保存できないエラーの対策
    // 参考：https://qiita.com/derasado/items/ff692411ef50f222af32
    use HasCompositePrimaryKeyTrait;

	protected $table = 'stl_model';
    protected $primaryKey = ['region_id', 'stl_type_id'];
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'stl_type_id' => 'int',
		'upload_datetime' => 'datetime'
	];

	protected $fillable = [
        'region_id',
        'stl_type_id',
		'stl_file',
		'upload_datetime',
        'solar_absorptivity',
        'heat_removal'
	];

	public function region()
	{
		return $this->belongsTo(Region::class, 'region_id');
	}

	public function stl_type()
	{
		return $this->belongsTo(StlType::class, 'stl_type_id');
	}
}
