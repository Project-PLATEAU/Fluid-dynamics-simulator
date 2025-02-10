<?php


namespace App\Services;

use App\Models\Db\Region;
use App\Models\Db\StlModel;
use App\Models\Db\StlType;
use App\Utils\DatetimeUtil;
use App\Utils\FileUtil;
use Faker\Core\Uuid;
use Illuminate\Database\Eloquent\Collection;
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
     * @param string $loginUserId ログインユーザ
     *
     * @return array 規追加結果(log含む)
     */
    public static function addNewOrUpdateRegion(Request $request, $city_model_id = "", $region_id = "", $loginUserId = "")
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

            // 新規作成の場合のみ
            if (!$region_id) {
                // ユーザID
                $region->user_id = $loginUserId;
                $logInfo .= "user_id: {$loginUserId}, ";
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
     * 解析対象地域の複製
     * @param Uuid $city_model_id 都市モデルID
     * @param Uuid $src_region_id 複製元の解析対象地域ID
     * @param string $replicate_to_region_name 複製先の識別名
     * @param string $login_user_id ログイン中のユーザID
     *
     * @return array 複製結果(log含む)
     */
    public static function copyRegion($city_model_id, $src_region_id, $replicate_to_region_name, $login_user_id)
    {
        $result = true;
        $logInfos = [];

        // 複製先の解析対象地域
        $newRegion = new Region();
        // 複製元の解析対象地域
        $srcRegion = self::getRegionById($src_region_id);

        // 解析対象地域テーブルを複製
        foreach ($srcRegion->getFillable() as $attribute) {
            if ($attribute == 'user_id') {
                // 複製したレコードの登録者をログインユーザとする
                $newRegion->{$attribute} = $login_user_id;
            } else if ($attribute == 'region_name') {
                $newRegion->{$attribute} = $replicate_to_region_name;
            } else if ($attribute != 'region_id') {
                $newRegion->{$attribute} = $srcRegion->{$attribute};
            }
        }

        if ($newRegion->save()) {
            $logInfo = "[region] [insert ] [copy from region: {$src_region_id}, with replicate_to_region_name: {$replicate_to_region_name}, user_id: {$login_user_id}]";
            array_push($logInfos, $logInfo);

            // ファイルストレージ内のOBJ / STLファイルも新規の解析対象地域IDフォルダ以下にOBJ / STLファイル種別ごとにコピーする。
            $srcObjStlPath = FileUtil::CITY_MODEL_FOLDER . "/{$city_model_id}/region/{$src_region_id}";
            $desObjStlPath = FileUtil::CITY_MODEL_FOLDER . "/{$city_model_id}/region/{$newRegion->region_id}";

            //複製元の解析対象地域解IDフォルダ以下にOBJ / STLファイルがある場合(1つ以上のファイルがアップロードされた)のみ、コピーをする。
            if (FileUtil::isExists($srcObjStlPath)) {
                $result = FileUtil::copyFolder($srcObjStlPath, $desObjStlPath);
                if ($result) {
                    $logInfo = "[OBJ / STL File] [copy from srcObjStlFiles: {$srcObjStlPath}]";
                    array_push($logInfos, $logInfo);

                    // STLファイルテーブルを複製
                    $result = self::copyStlModel($src_region_id, $newRegion->region_id, $desObjStlPath);
                    if ($result) {
                        $logInfo = "[stl_model] [insert] [copy from region: {$src_region_id}]";
                        array_push($logInfos, $logInfo);
                    }
                }
            }
        } else {
            $result = false;
        }
        return ["result" => $result, "log_infos" => $logInfos];
    }

    /**
     * STLファイルのレコードを複製
     * @param Uuid $src_region_id コピー元の解析対象地域ID
     * @param Uuid $des_region_id コピー先の解析対象地域ID
     * @param string $des_obj_stl_folder_path コピー先のOBJ / STLファイルを格納するフォルダパス
     *
     * @return bool
     *  複製に成功した場合、true
     *  複製に失敗した場合、false
     */
    public static function copyStlModel($src_region_id, $des_region_id, $des_obj_stl_folder_path)
    {
        $result = true;

        // 複製元のSTLファイルのレコードを取得
        $srcStlModels = self::getStlModels($src_region_id);
        foreach ($srcStlModels as $srcStlModel) {

            // 複製元のSTLファイル名を取得する。
            $objStlFileName = FileUtil::getFileName($srcStlModel->stl_file);
            // 複製先のSTLファイルパス設定：city_model/<city_model_id>/region/<region_id>/<stl_type_id>/STLファイル名
            $desObjStlFilePath = $des_obj_stl_folder_path . "/" . $srcStlModel->stl_type_id . "/" . $objStlFileName;

            // 複製元のczmlファイル名を取得する。
            $czmlFileName = FileUtil::getFileName($srcStlModel->czml_file);
            // 複製先のczmlファイルパス設定：city_model/<city_model_id>/region/<region_id>/<stl_type_id>/czmlファイル名
            $desCzmlFilePath = $des_obj_stl_folder_path . "/" . $srcStlModel->stl_type_id . "/" . $czmlFileName;

            $newStlModel = new StlModel();
            foreach ($srcStlModel->getFillable() as $attribute) {
                if ($attribute == 'region_id') {
                    // 解析対象地域IDには新規に割り当てられたCA1解析対象地域IDを設定
                    $newStlModel->{$attribute} = $des_region_id;
                } elseif ($attribute == 'stl_file') {
                    // STLファイルにはコピーして作成されたOBJ/STLファイルのパスを設定
                    $newStlModel->{$attribute} = $desObjStlFilePath;
                } elseif ($attribute == 'czml_file') {
                   // czmlファイルにはコピーして作成されたczmlファイルのパスを設定
                    $newStlModel->{$attribute} = $desCzmlFilePath;
                } else {
                    // その他の項目にはコピー元の値のままに設定
                    $newStlModel->{$attribute} = $srcStlModel->{$attribute};
                }
            }
            if (!$newStlModel->save()) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * 解析対象地域IDに紐づくSTLファイルのレコードを全て取得
     * @param Uuid $region_id 解析対象地域ID
     *
     * @return Collection App\Models\Db\StlModel
     */
    public static function getStlModels($region_id)
    {
        return StlModel::where(['region_id' => $region_id])->get();
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

    /**
     * シミュレーションモデルの建物データ(czmlファイルパス)を取得する。
     * @param Collection 'App\Models\Db\StlModel $stlFiles STLファイルのコレクション
     *
     * @return array
     */
    public static function getCzmlFiles($stlFiles)
    {
        // 3D地図表示のため、特定の解析対象地域に紐づいていたczmlファイルを取得する。
        // 「解析対象地域ID」に紐づいた複数の「STLファイル種別ID」ですが、すべてczmlファイル表示対象となる。
        $czmlFiles = $stlFiles->filter(function ($stl_model) {
            // 表示対象はSTLファイル種別テーブル.地表面フラグ=0(false) （すなわち建物）のSTLファイルよりレコードを取得します
            if (!$stl_model->stl_type->ground_flag) {
                return $stl_model;
            }
        })->map(function ($stl_model) {
            // CZMLファイルパスをリターンする。(czml_fileがnullの場合は、空文字("")をリターンする。)
            return $stl_model->czml_file ? FileUtil::referenceStorageFile($stl_model->czml_file) : "";
        })->values()->toArray();

        return $czmlFiles;
    }

    /**
     * 3D地図描画に必要な全てCZMLファイルが出来上がったか定期的に確認する。
     *
    *  @param Uuid $region_id 解析対象地域ID
     * @param integer $timeout タイムアウト[秒]
     * @return array 確認結果
     */
    public static function waitCzmlFile($region_id, $timeout = 5)
    {
        $checkResult['type'] = 'timeout';

        $interval = 1;
        $count = $timeout / $interval;

        for ($i = 0; $i < $count; $i++) {

            // 解析対象地域
            $region = self::getRegionById($region_id);
            // STLファイル
            $stlFiles = $region->stl_models()->get();
            // 3D地図表示のため、特定の解析対象地域に紐づいていたczmlファイルを取得する。
            // 「解析対象地域ID」に紐づいた複数の「STLファイル種別ID」ですが、すべてczmlファイル表示対象となります。
            $czmlFiles = RegionService::getCzmlFiles($stlFiles);

            // 全てCZMLファイルが揃ったタイミングでは、すぐにクライアントにレスポンスする。(ロングポーリング)
            if ((count($czmlFiles) > 0) && !in_array("", $czmlFiles)) {
                $checkResult['type'] = 'fire';
                $checkResult['czmlFiles'] = $czmlFiles;
                break;
            }

            // CPU負荷を下げるために少し待機
            usleep(500000); // 0.5秒待機
        }

        return $checkResult;
    }

    /**
     * 解析対象地域を編集することができるかをチェックする。
     *
     * @param Uuid $region_id 解析対象地域ID
     *
     * @return bool チェックを行った結果
     */
    public static function editIsOK($region_id)
    {
        $isOk = true;
        // 解析対象地域
        $region = self::getRegionById($region_id);
        // 解析対象地域に属しているシミュレーションモデルを取得する。
        $simulationModels = $region->simulation_models()->get();

        foreach($simulationModels as $simulationModel) {
            // 取得した「(SM) シミュレーションモデル」レコードのいずれかの「実行ステータス」が「0.未」or null以外
            // (1.実行中;2.正常終了;3.異常終了;4.中止;5.管理者中止）)の場合、編集不可とします。
            if ($simulationModel->run_status) {
                $isOk = false;
                break;
            }
        }
        return $isOk;
    }
}
