<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Models\DbModel;

/**
 * Class Information
 *
 * @property int $information_id
 * @property string|null $information
 *
 * @package App\Models\Db
 */
class Information extends DbModel
{
	protected $table = 'information';
	protected $primaryKey = 'information_id';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'information_id' => 'int'
	];

	protected $fillable = [
		'information'
	];
}
