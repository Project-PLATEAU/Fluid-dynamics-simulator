<?php


namespace App\Services;

use App\Commons\CommonUtils;
use App\Commons\Constants;
use App\Models\Db\CityModel;
use App\Models\Db\CityModelReferenceAuthority;
use App\Models\Db\Coordinate;
use App\Models\Db\StlType;
use App\Utils\DatetimeUtil;
use App\Utils\LogUtil;
use Illuminate\Database\Eloquent\Collection;

/**
 * 都市モデルサービス
 */
class CityModelService extends BaseService
{

    /**
     *
     * 都市モデル一覧を取得
     *
     * @param string $login_user ログインユーザ
     * @return \App\Models\DB\CityModel
     */
    public static function getCityModelList($login_user_id)
    {
            $cityModelList = CityModel::where("registered_user_id", $login_user_id)
            ->orWhere("preset_flag", true) // 都市モデルテーブル]のプリセットフラグが有効である。
            ->orWhereHas('city_model_reference_authoritys',function ($query) use ($login_user_id) {
                    $query->where('user_id', $login_user_id);
            })
            ->orderBy('last_update_datetime', 'desc') // 最終更新日時
            ->take(Constants::SELECT_LIMIT) // 最大表示件数
            ->get();
        return $cityModelList;
    }

    /**
     *
     * 都市モデル削除
     * @param mixed $id 都市モデルID
     *
     * @return
     */
    public static function deleteCityModelById($id)
    {
        // 都市モデル参照権限テーブルから削除
        CityModelReferenceAuthority::destroy($id);
        LogUtil::i("[city_model_reference_authority] [delete] [city_model_id: {$id}]");

        // 都市モデルテーブルから削除
        CityModel::destroy($id);
        LogUtil::i("[city_model] [delete] [city_model_id: {$id}]");
    }

    /**
     * 都市モデルの新規追加
     *
     * @param string $registered_user_id 登録ユーザID
     * @param string $identification_name 識別名
     * @param string $url URL
     *
     * @return bool
     *  レコード登録に成功した場合、true
     *  レコード登録に失敗した場合、false
     */
    public static function addNewCityModel($registered_user_id, $identification_name, $url)
    {
        $cityModel = new CityModel();
        $cityModel->identification_name = $identification_name;
        $cityModel->registered_user_id = $registered_user_id;
        $cityModel->last_update_datetime = DatetimeUtil::getNOW();
        $cityModel->preset_flag = false;
        $cityModel->url = $url;

        if ($cityModel->save()) {
            LogUtil::i("[city_model] [insert] [identification_name: {$identification_name}, registered_user_id: {$registered_user_id}, last_update_datetime: {$cityModel->last_update_datetime}, preset_flag: false, url: {$url}]");
            return true;
        } else {
            return false;
        }
    }

    /**
     * 都市モデルのレコード更新
     * @param integer $id 都市モデルID
     * @param string $attribute 更新対象カラム
     * @param string $val 更新値
     *
     * @return bool 更新結果
     *  更新に成功した場合、true
     *  更新に失敗した場合、false
     */
    public static function updateCityModelById($id, $attribute, $val)
    {
        $cityModel = self::getCityModelById($id);

        if ($cityModel && (array_search($attribute, $cityModel->getFillable()) >= 0)) {
            $cityModel->{$attribute} = $val;
            if ($attribute != 'last_update_datetime') {
                $cityModel->last_update_datetime = DatetimeUtil::getNOW();
            }
            if ($cityModel->save()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     *
     * 都市モデルIDでレコード取得
     *
     * @param mixed $id 都市モデルID
     *
     * @return \App\Models\Db\CityModel
     */
    public static function getCityModelById($id)
    {
        $cityModel = CityModel::find($id);
        return $cityModel;
    }

    /**
     *
     * 識別名と登録ユーザでレコード取得
     *
     * @param string $identification_name 識別名
     * @param string $registered_user_id 登録ユーザID
     *
     * @return array App\Models\Db\CityModel
     */
    public static function getCityModelByIdentificationNameAndUser($identification_name, $registered_user_id)
    {
        $cityModel = CityModel::where('identification_name', $identification_name)
            ->where('registered_user_id', $registered_user_id)
            ->get();
        return $cityModel->toArray();
    }

    /**
     * 3D タイル選択欄を取得
     * @return array 3D タイル選択肢の配列
     */
    public static function get3DTilesOptions()
    {
        // 「未選択」オプション
        $notSelectOption = [['name' => '未選択', 'type_en' => '', 'url' => '', 'lod' => '']];
        // 特定の3d tiles
        $filtered3dtiles = CommonUtils::filter3Dtiles();
        // 3D タイル選択欄
        $_3dTilesOptions = array_merge($notSelectOption, $filtered3dtiles);
        return $_3dTilesOptions;
    }

    /**
     * 特定3D タイルのURLを取得
     * @param integer $index 特定3D タイルインデックス
     * @return string  特定3D タイルのURL
     */
    public static function get3DTilesByIndex($index)
    {
        $_3dTilesOptions = self::get3DTilesOptions();
        return $_3dTilesOptions[$index]['url'];
    }

    /**
     * 平面角直角座標系の選択欄を取得
     * @return Collection \App\Models\Db\Coordinate 平面角直角座標系の選択肢の配列
     */
    public static function getCoordinateOptions()
    {
        // 平面角直角座標系
        return Coordinate::all();
    }

    /**
     * STLファイル種別の選択欄を取得
     * @return Collection 'App\Models\Db\StlType STLファイル種別の選択肢の配列
     */
    public static function getStlTypeOptions()
    {
        // 平面角直角座標系
        return StlType::all();
    }
}
