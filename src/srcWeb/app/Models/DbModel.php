<?php

namespace App\Models;

use App\Commons\Constants;
use App\Services\UserAccountService;
use App\Utils\StringUtil;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * モデルの共通クラス
 */
class DbModel extends Model
{
    use HasFactory;

    /**
     * コンストラクタ
     *
     * @param  array  $attributes 属性配列
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * 更新ユーザ名を取得。
     * @param  $share_mode 都市モデル：1, シミュレーションモデル：2
     * @param integer $id 都市モデルID or シミュレーションモデルID
     *
     * @return string 文字列に変換済みの更新ユーザ名
     */
    public function getUpdateUser($share_mode = Constants::SHARE_MODE_CITY_MODEL, $id)
    {
        $updateUser = "";
        if ($this->preset_flag) {
            // プリセットフラグが有効であれば「ALL」
            $updateUser  = "ALL";
        } else {
            // そうでなければ、[都市モデル参照権限テーブル]を持つ各ユーザに関する
            // [ユーザアカウントテーブル]の表示名を半角スラッシュ記号(/)で連結して表示する
            // 都市モデル参照権限テーブルのユーザIDリスト
            $displayNameArr = UserAccountService::getDisplayNameArr($share_mode, $id);
            $updateUser = StringUtil::arrayToString($displayNameArr, StringUtil::SEPARATE_SLASH);
        }
        return $updateUser;
    }
}
