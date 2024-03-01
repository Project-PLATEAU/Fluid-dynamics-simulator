<?php


namespace App\Services;

use App\Models\Db\SimulationModelReferenceAuthority;
use App\Utils\DatetimeUtil;
use App\Utils\LogUtil;
use Faker\Core\Uuid;

/**
 * シミュレーションモデル参照権限サービス
 */
class SimulationModelReferenceAuthorityService extends BaseService
{

    /**
     * シミュレーションモデル参照権限の新規追加
     *
     * @param string $user_id 共有ユーザID
     * @param Uuid $simulation_model_id シミュレーションモデルID
     *
     * @return bool
     *  レコード登録に成功した場合、true
     *  レコード登録に失敗した場合、false
     */
    public static function addNewSimulationModelReferenceAuthority($user_id, $simulation_model_id)
    {
        $SimulationModelReferenceAuthority = new SimulationModelReferenceAuthority();
        $SimulationModelReferenceAuthority->simulation_model_id = $simulation_model_id;
        $SimulationModelReferenceAuthority->user_id = $user_id;
        $SimulationModelReferenceAuthority->last_update_datetime = DatetimeUtil::getNOW();

        if ($SimulationModelReferenceAuthority->save()) {
            LogUtil::i("[SimulationModelReferenceAuthority] [insert] [simulation_model_id: {$simulation_model_id}, user_id: {$user_id}, last_update_datetime: {$SimulationModelReferenceAuthority->last_update_datetime}");
            return true;
        } else {
            return false;
        }
    }

    /**
     * シミュレーションモデル参照権限の削除
     *
     * @param string $user_id 共有ユーザID
     * @param Uuid $simulation_model_id シミュレーションモデルID
     *
     * @return bool
     *  レコード削除に成功した場合、true
     *  レコード削除に失敗した場合、false
     */
    public static function deleteSimulationModelReferenceAuthority($userIdsArray, $simulation_model_id)
    {
        $result = SimulationModelReferenceAuthority::where('simulation_model_id', $simulation_model_id)
        ->whereIn('user_id', $userIdsArray)
        ->delete();

        if ($result >= 1) {
            LogUtil::i("[simulation_model_reference_authority] [delete] [city_model_id: {$simulation_model_id}, user_id " .implode(', ', $userIdsArray). "]");
            return true;
        } else {
            return false;
        }
    }
}
