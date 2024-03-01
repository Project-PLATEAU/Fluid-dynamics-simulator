<?php


namespace App\Services;

use App\Models\Db\CityModelReferenceAuthority;
use App\Utils\DatetimeUtil;
use App\Utils\LogUtil;
use Faker\Core\Uuid;

/**
 * 都市モデル参照権限サービス
 */
class CityModelReferenceAuthorityService extends BaseService
{

    /**
     * 都市モデル参照権限の新規追加
     *
     * @param string $user_id 共有ユーザID
     * @param Uuid $city_model_id 都市モデルID
     *
     * @return bool
     *  レコード登録に成功した場合、true
     *  レコード登録に失敗した場合、false
     */
    public static function addNewCityModelReferenceAuthority($user_id, $city_model_id)
    {
        $cityModelReferenceAuthority = new CityModelReferenceAuthority();
        $cityModelReferenceAuthority->city_model_id = $city_model_id;
        $cityModelReferenceAuthority->user_id = $user_id;
        $cityModelReferenceAuthority->registered_datetime = DatetimeUtil::getNOW();

        if ($cityModelReferenceAuthority->save()) {
            LogUtil::i("[city_model_reference_authority] [insert] [city_model_id: {$city_model_id}, user_id: {$user_id}, registered_datetime: {$cityModelReferenceAuthority->registered_datetime}");
            return true;
        } else {
            return false;
        }
    }

    /**
     * 都市モデル参照権限の削除
     *
     * @param string $user_id 共有ユーザID
     * @param Uuid $city_model_id 都市モデルID
     *
     * @return bool
     *  レコード削除に成功した場合、true
     *  レコード削除に失敗した場合、false
     */
    public static function deleteCityModelReferenceAuthority($userIdsArray, $city_model_id)
    {
        $result = CityModelReferenceAuthority::where('city_model_id', $city_model_id)
            ->whereIn('user_id', $userIdsArray)
            ->delete();
        if ($result >= 1) {
            LogUtil::i("[city_model_reference_authority] [delete] [city_model_id: {$city_model_id}, user_id: " .implode(', ', $userIdsArray). "]");
            return true;
        } else {
            return false;
        }
    }
}
