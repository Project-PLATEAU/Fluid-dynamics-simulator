<?php


namespace App\Services;

use App\Commons\Constants;
use App\Models\Db\CityModelReferenceAuthority;
use App\Models\Db\SimulationModelReferenceAuthority;
use App\Models\Db\UserAccount;

/**
 * ユーザアカウントのサービス
 */
class UserAccountService extends BaseService
{
    /**
     * ユーザの表示名を取得
     * @param  $share_mode 都市モデル：1, シミュレーションモデル：2
     * @param  $id 都市モデルID or シミュレーションモデルID
     *
     * @return array ユーザの表示名リスト
     */
    public static function getDisplayNameArr($share_mode = Constants::SHARE_MODE_CITY_MODEL, $id)
    {
        $userIdArr = [];

        // 都市モデル参照権限テーブルのユーザIDリスト
        if ($share_mode == Constants::SHARE_MODE_CITY_MODEL) {
            $userIdArr = CityModelReferenceAuthority::select('user_id')->where('city_model_id', $id)->get();
        } elseif ($share_mode == Constants::SHARE_MODE_SIMULATION_MODEL) {
            $userIdArr = SimulationModelReferenceAuthority::select('user_id')->where('simulation_model_id', $id)->get();
        }
        $displayNameCollection = UserAccount::select('display_name')->whereIn('user_id', $userIdArr)->get()
            ->map(function ($item) {
                return $item->display_name;
            });
        $displayNameArr = $displayNameCollection->toArray();
        return $displayNameArr;
    }
}
