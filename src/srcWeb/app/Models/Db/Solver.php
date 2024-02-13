<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Db;

use App\Commons\Constants;
use App\Models\DbModel;
use Carbon\Carbon;
use Faker\Core\Uuid;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Solver
 *
 * @property Uuid $solver_id
 * @property string|null $solver_name
 * @property string|null $solver_compressed_file
 * @property string $user_id
 * @property Carbon|null $upload_datetime
 * @property bool|null $preset_flag
 * @property bool|null $disclosure_flag
 *
 * @property UserAccount $user_account
 * @property Collection|SimulationModel[] $simulation_models
 *
 * @package App\Models
 */
class Solver extends DbModel
{
	protected $table = 'solver';
	protected $primaryKey = 'solver_id';
    /**
     * 主キーのデータ型がuuidのため、主キータイプをstringに変更する必要がある。(デフォルト：int)
     */
    protected $keyType = 'string';
	public $timestamps = false;

	protected $casts = [
		'upload_datetime' => 'datetime',
		'preset_flag' => 'bool',
		'disclosure_flag' => 'bool'
	];

	protected $fillable = [
		'solver_name',
		'solver_compressed_file',
		'user_id',
		'upload_datetime',
		'preset_flag',
		'disclosure_flag'
	];

	public function user_account()
	{
		return $this->belongsTo(UserAccount::class, 'user_id');
	}

	public function simulation_models()
	{
		return $this->hasMany(SimulationModel::class, 'solver_id');
	}

    /**
     * 公開状況を取得
     * @return string 公開状況
     */
    public function getPublicStatus()
    {
        // SC7公開フラグが有効であれば「公開」、無効であれば「非公開」と表示する
        if ($this->disclosure_flag) {
            return Constants::PUBLIC;
        } else {
            return Constants::NON_PUBLIC;
        }
    }

    /**
     * 登録ユーザを取得
     * @return string 登録ユーザ
     */
    public function getUserName()
    {
        // SC6プリセットフラグが有効であれば空欄とし、無効であればSC4登録ユーザIDに基づくUA3表示名を表示する
        if ($this->preset_flag) {
            return "";
        } else {
            return $this->user_account->display_name;
        }
    }

    /**
     * プリセットフラグにより、レコードを取得する。
     * @param bool $preset_flag プリセットフラグ(※default: true-有効)
     *
     * @return self
     */
    public static function getByPresetFlag($preset_flag = true)
    {
        return self::where('preset_flag', $preset_flag)->first();
    }
}
