<?php


namespace App\Services;

use App\Models\Db\Region;
use App\Models\Db\StlModel;
use App\Models\Db\StlType;
use App\Utils\DatetimeUtil;
use App\Utils\FileUtil;
use Faker\Core\Uuid;
use Illuminate\Http\Request;

/**
 * 解析対象地域サービス
 */
class RegionService extends BaseService
{

    /**
     * 解析対象地域の削除
     * @param Uuid $city_model_id 都市モデルID
     * @param Uuid $region_id 解析対象地域ID
     *
     * @return array 削除結果(log含む)
     */
    public static function deleteRegionById($city_model_id, $region_id)
    {
        $result = true;
        $logInfos = [];

        // 解析対象地域の削除
        if (Region::destroy($region_id) > 0) {
            array_push($logInfos, "[region] [delete] [region_id: {$region_id}]");
        } else {
            $result = false;
        }

        // 都市モデルの更新
        $now = DatetimeUtil::getNOW();
        if (CityModelService::updateCityModelById($city_model_id, 'last_update_datetime', $now )) {
            array_push($logInfos, "[city_model] [update] [city_model_id: {$city_model_id}, last_update_datetime: {$now}]");
        } else {
            $result = false;
        }
        return ["result" => $result, "log_infos" => $logInfos];
    }


    /**
     * 解析対象地域の新規追加
     * @param Request $request リクエスト
     * @param Uuid $region_id 解析対象地域ID
     * @param Uuid $city_model_id 都市モデルID
     *
     * @return array 規追加結果(log含む)
     */
    public static function addNewOrUpdateRegion(Request $request, $city_model_id = "", $region_id = "")
    {
        $result = true;
        $logInfos = [];

        $region = new Region();
        $logInfo = "[region] [insert] [";
        if ($region_id) {
            $region = self::getRegionById($region_id);
            $logInfo = "[region] [update] [";
        }

        if ($region) {
            // 都市モデルID
            if ($city_model_id) {
                $region->city_model_id = $city_model_id;
                $logInfo .= "city_model_id: {$city_model_id}, ";
            }
            // 対象地域識別名
            if ($request->region_name) {
                $region->region_name = $request->region_name;
                $logInfo .= "region_name: {$request->region_name}, ";
            }
            // 平面直角座標系ID
            if ($request->coordinate_id) {
                $region->coordinate_id = $request->coordinate_id;
                $logInfo .= "coordinate_id: {$request->coordinate_id}, ";
            }
            // 南端緯度
            if ($request->south_latitude) {
                $region->south_latitude = $request->south_latitude;
                $logInfo .= "south_latitude: {$request->south_latitude}, ";
            }
            // 北端緯度
            if ($request->north_latitude) {
                $region->north_latitude = $request->north_latitude;
                $logInfo .= "north_latitude: {$request->north_latitude}, ";
            }
            // 西端経度
            if ($request->west_longitude) {
                $region->west_longitude = $request->west_longitude;
                $logInfo .= "west_longitude: {$request->west_longitude}, ";
            }
            // 東端経度
            if ($request->east_longitude) {
                $region->east_longitude = $request->east_longitude;
                $logInfo .= "east_longitude: {$request->east_longitude}, ";
            }
            // 地面高度
            if ($request->ground_altitude) {
                $region->ground_altitude = $request->ground_altitude;
                $logInfo .= "ground_altitude: {$request->ground_altitude}, ";
            }
            // 上空高度
            if ($request->sky_altitude) {
                $region->sky_altitude = $request->sky_altitude;
                $logInfo .= "sky_altitude: {$request->sky_altitude}, ";
            }

            $logInfo .= "]";
        }

        // 解析対象地域の新規追加
        if ($region->save()) {
            array_push($logInfos, $logInfo);
        } else {
            $result = false;
        }

        // 都市モデルの更新
        $now = DatetimeUtil::getNOW();
        if (CityModelService::updateCityModelById($city_model_id, 'last_update_datetime', $now)) {
            array_push($logInfos, "[city_model] [update] [city_model_id: {$city_model_id}, last_update_datetime: {$now}]");
        } else {
            $result = false;
        }
        return ["result" => $result, "log_infos" => $logInfos];
    }


    /**
     * 解析対象地域IDで解析対象地域を取得する。
     * @param Uuid $region_id 解析対象地域ID
     *
     * @return Region 解析対象地域
     */
    public static function getRegionById($region_id)
    {
        return Region::find($region_id);
    }

    /**
     * STLファイルのレコードを取得
     * @param Uuid $region_id 解析対象地域ID
     * @param integer $stl_type_id STLファイル種別ID
     *
     * @return StlModel
     */
    public static function getSltFile($region_id, $stl_type_id)
    {
        return StlModel::where(['region_id' => $region_id, 'stl_type_id' => $stl_type_id])->first();
    }

    /**
     * STLファイルをアップロード
     * @param Uuid $city_model_id 都市モデルID
     * @param Uuid $region_id 解析対象地域ID
     * @param integer $stl_type_id STLファイル種別ID
     * @param Request $stl_file_rq 選択したSTLファイル
     *
     * @return string STLファイルの相対パス
     */
    public static function uploadStlFile($city_model_id, $region_id, $stl_type_id, $stl_file_rq)
    {
        $stlFile = $stl_file_rq->getClientOriginalName();
        $stlFileRelativePath = FileUtil::CITY_MODEL_FOLDER . "/{$city_model_id}/region/{$region_id}/{$stl_type_id}/";
        FileUtil::upload($stl_file_rq, $stlFileRelativePath, $stlFile);
        // city_model/<city_model_id>/region/<region_id>/<stl_type_id>/STLファイル名
        $_stlFileRelativePath = $stlFileRelativePath . $stlFile;
        return $_stlFileRelativePath;
    }

    /**
     * STLファイルの新規追加または更新
     * @param Uuid $city_model_id 解析対象地域ID
     * @param Uuid $region_id 解析対象地域ID
     * @param integer $stl_type_id STLファイル種別ID
     * @param Request $stl_file_rq 選択したSTLファイル
     * @param float $solar_absorptivity 入力した日射吸収率
     * @param float $heat_removal 入力した排熱量
     *
     * @return array 新規追加または更新の結果(log含む)
     */
    public static function addNewOrUpdateStlFile($city_model_id, $region_id, $stl_type_id, $stl_file_rq, $solar_absorptivity, $heat_removal)
    {
        $result = true;
        $logInfos = [];
        $logInfo = "[stl_model] [update] [region_id: {$region_id}, stl_type_id: {$stl_type_id}, ";
        $sltFile = self::getSltFile($region_id, $stl_type_id);
        if (!$sltFile) {
            // レコードが存在しない場合、新規作成を行う。
            $sltFile = new StlModel();
            $sltFile->region_id = $region_id;
            $sltFile->stl_type_id = $stl_type_id;
            $logInfo = "[stl_model] [insert] [region_id: {$region_id}, stl_type_id: {$stl_type_id}, ";
        }

        // STLファイルをアップロード
        $stlFileRelativePath = self::uploadStlFile($city_model_id, $region_id, $stl_type_id, $stl_file_rq);
        // STLファイルアップロードに成功したら、相対パスを保存
        $sltFile->stl_file = $stlFileRelativePath;
        $logInfo .= "stl_file: {$stlFileRelativePath}, ";

        // 登録日時を現在日時とする
        $now = DatetimeUtil::getNOW();
        $sltFile->upload_datetime = $now;
        $logInfo .= "upload_datetime: {$now}, ";

        // 日射吸収率
        $sltFile->solar_absorptivity = $solar_absorptivity;
        $logInfo .= "solar_absorptivity: {$solar_absorptivity}, ";

        // 排熱量
        $sltFile->heat_removal = $heat_removal;
        $logInfo .= "heat_removal: {$heat_removal}]";

        // 保存
        $result = $sltFile->save();

        if ($result) {

            array_push($logInfos,$logInfo);

            // 都市モデルの更新
            if (CityModelService::updateCityModelById($city_model_id, 'last_update_datetime', $now)) {
                array_push($logInfos, "[city_model] [update] [city_model_id: {$city_model_id}, last_update_datetime: {$now}]");
            } else {
                $result = false;
            }
        }
        return ["result" => $result, "log_infos" => $logInfos];
    }

    /**
     * STLファイルの削除
     * @param Uuid $city_model_id 都市モデルID
     * @param Uuid $region_id 解析対象地域ID
     * @param integer $stl_type_id STLファイル種別ID
     *
     * @return array 削除結果(log含む)
     */
    public static function deleteStlFile($city_model_id, $region_id, $stl_type_id)
    {
        $result = true;
        $logInfos = [];

        // STLファイルの削除
        if (StlModel::where(['region_id' => $region_id, 'stl_type_id' => $stl_type_id])->delete()) {
            array_push($logInfos, "[slt_model] [delete] [region_id: {$region_id}, stl_type_id: {$stl_type_id}]");
        } else {
            $result = false;
        }

        // 都市モデルの更新
        $now = DatetimeUtil::getNOW();
        if (CityModelService::updateCityModelById($city_model_id, 'last_update_datetime', $now)) {
            array_push($logInfos, "[city_model] [update] [city_model_id: {$city_model_id}, last_update_datetime: {$now}]");
        } else {
            $result = false;
        }
        return ["result" => $result, "log_infos" => $logInfos];
    }

    /**
     * 特定のSTLファイル種別を取得
     * @param integer $stl_type_id STLファイル種別ID
     *
     * @return StlType STLファイル種別
     */
    public static function getStlType($stl_type_id)
    {
        // STLファイル種別
        return StlType::where('stl_type_id', $stl_type_id)->first();
    }
}
