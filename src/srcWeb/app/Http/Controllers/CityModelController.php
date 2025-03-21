<?php

namespace App\Http\Controllers;

use App\Commons\Constants;
use App\Commons\Message;
use App\Services\CityModelService;
use App\Services\RegionService;
use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 都市モデル関連画面用のコントロール
 */
class CityModelController extends BaseController
{
    /**
     * 都市モデル一覧画面の初期表示
     */
    public function index()
    {
        try {
            $cityModelList = CityModelService::getCityModelList(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id);

            // 他画面からのリダイレクトで渡されたデータを受け取る。
            $message = session('message') ? session('message') : null;
            $cityModelId = session('citymodelId') ? session('citymodelId') : null;

            return view('city_model.index', compact('cityModelList', 'message', 'cityModelId'));
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 都市モデル追加画面を表示する。
     */
    public function create()
    {
        // 他画面からのリダイレクトで渡されたデータを受け取る。
        $message = session('message');

        // 3D タイル選択欄
        $_3dTilesOptions = CityModelService::get3DTilesOptions();

        return view('city_model.create', compact('message', '_3dTilesOptions'));
    }

    /**
     * 都市モデル保存を行う。
     */
    public function store(Request $request)
    {
        try {

            $errorMessage = [];

            $identificationName = $request->identification_name;

            if (!$identificationName) {
                // 識別名が未入力
                $errorMessage = ["type" => "E", "code" => "E9", "msg" => Message::$E9];
            } else {

                // 選択した3d タイル
                $_3dtilesIndex = $request->_3dtiles;
                if(!$_3dtilesIndex) {
                    // 3d タイルが選択されなかった場合、エラー「E22」を出す。
                    $errorMessage = ["type" => "E", "code" => "E22", "msg" => Message::$E22];
                } else {

                    // ログイン中のユーザID
                    $userId = self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id;
                    $cityModel = CityModelService::getCityModelByIdentificationNameAndUser($identificationName, $userId);
                    if ($cityModel) {
                        // 追加しようとする識別名が既に存在する場合、エラーを出す。
                        $errorMessage = ["type" => "E", "code" => "E10","msg" => Message::$E10];
                    }
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.create')->with(['message' => $errorMessage]);
            } else {
                $url = CityModelService::get3DTilesByIndex($_3dtilesIndex);
                $result = CityModelService::addNewCityModel(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id, $identificationName, $url);
                if ($result) {
                    return redirect()->route('city_model.index');
                } else {
                    throw new Exception("都市モデル新規作成に失敗しました。識別名：{$identificationName}");
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     *
     * 都市モデル閲覧画面を表示する。
     *
     * @param string $id 都市モデルID
     *
     * @return
     */
    public function show(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            if ($id == 0) {
                // 表示対象の都市モデルが未選択
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else {
                // CityGmlファイルアップロード機能がなくなったことでE14のチェックを無効にする。
                // 表示対象の都市モデルのCityGMLファイルが未登録
                // $gmlArr = CityModelService::getGmlByCityModelId($id);
                // if (!$gmlArr) {
                //     $errorMessage = ['type'=> "E", "code" => "E14", "msg"=> Message::$E14];
                // }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.index')->with(['message' => $errorMessage]);
            } else {
                $cityModel = CityModelService::getCityModelById($id);
                return view('city_model.view', compact('cityModel'));
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     *
     * 都市モデル付帯情報編集画面を表示する。
     *
     * @param string $id 都市モデルID
     *
     * @return
     */
    public function edit(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 編集操作ができるか確認
            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                $regionDeleteFlg = $request->query->get('region_delete');
                if (!$regionDeleteFlg) {
                    return redirect()->route('city_model.index')->with(['message' => $errorMessage]);
                }
            }

            $cityModel = CityModelService::getCityModelById($id);

            // 更新処理に失敗時のエラーなど
            $message = session('message');

            // 3D タイル選択欄
            $_3dTilesOptions = CityModelService::get3DTilesOptions();

            // 平面角直角座標系の選択欄
            $coordinateOptions = CityModelService::getCoordinateOptions();

            // STLファイル種別の選択欄
            $stlTypeOptions = CityModelService::getStlTypeOptions();
            // STLファイル種別の選択欄
            $stlTypeOptionsByGroundFalse = CityModelService::getStlTypeOptionsByGroundFlagFalse();

            // 解析対象地域削除やSTLファイルアップロード・削除時等に選択した解析対象地域
            $regionId = $request->query->get('region_id');
            // STLファイル削除時に選択したSTLファイル種別ID
            $stlTypeId = $request->query->get('stl_type_id');

            $loginUserId = self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id;

            return view('city_model.edit', compact('cityModel', 'registeredUserId', 'message', '_3dTilesOptions', 'coordinateOptions', 'stlTypeOptions', 'stlTypeOptionsByGroundFalse', 'regionId', 'stlTypeId', 'loginUserId'));

        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     *
     * 都市モデル更新を行う。
     *
     * @param Request $request リクエスト
     * @param string $id 都市モデルID
     *
     * @return
     */
    public function update(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            // ログイン中のユーザID
            $userId = self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id;
            // 識別名
            $identificationName = $request->identification_name;
            // 選択した3d タイル
            $_3dtilesIndex = $request->_3dtiles;

            // region_id を URL から取得
            $region_id = $request->query('region_id');

            // 識別名の更新
            if (!$identificationName) {
                // 識別名が未入力
                $errorMessage = ["type" => "E", "code" => "E9", "msg" => Message::$E9];
            } elseif ($cityModel = CityModelService::getCityModelByIdentificationNameAndUser($identificationName, $userId, $id)) {
                // 編集しようとする識別名が既に存在する場合、エラーを出す。
                $errorMessage = ["type" => "E", "code" => "E10", "msg" => Message::$E10];
            } elseif (!$_3dtilesIndex) {
                // 3d タイルが選択されなかった場合、エラー「E22」を出す。
                $errorMessage = ["type" => "E", "code" => "E22", "msg" => Message::$E22];
            } elseif (!$region_id) {
                // E2 判断 (元の第一メソッドから)
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
            } else {
                DB::beginTransaction();
                $updateResult = false;
                $updateAttribute = "";
                $cityModelLogInfo = "";

                // 識別名更新
                $updateAttribute = "識別名";
                $updateResult = CityModelService::updateCityModelById($id, 'identification_name', $identificationName);

                if (!$updateResult) {
                    throw new Exception("都市モデル更新に失敗しました。都市モデルID: {$id}、 更新対象：{$updateAttribute}");
                }

                // 3d tile更新
                $updateAttribute = "URL";
                $url = CityModelService::get3DTilesByIndex($_3dtilesIndex);
                $updateResult = CityModelService::updateCityModelById($id, 'url', $url);
                if (!$updateResult) {
                    throw new Exception("都市モデル更新に失敗しました。都市モデルID: {$id}、 更新対象：{$updateAttribute}");
                }
                $cityModelLogInfo = "[city_model] [update] [city_model_id: {$id}, identification_name: {$identificationName}, url: {$url}";
                LogUtil::i($cityModelLogInfo);
                // 解析対象地域の更新
                $updateResult = RegionService::addNewOrUpdateRegion($request, $id, $region_id);
                if ($updateResult['result']) {
                    foreach ($updateResult['log_infos'] as $key => $log) {
                        // region表の情報をログに記録
                        LogUtil::i($log);
                    }
                } else {
                    throw new Exception("解析対象地域の更新に失敗しました。解析対象地域ID: {$region_id}");
                }
                DB::commit();
            }
            return redirect()->route('city_model.edit', ['id' => $id, 'registered_user_id' => request()->query('registered_user_id')])->with(['message' => $errorMessage]);
        } catch (Exception $e) {
            DB::rollback();
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     *
     * 都市モデル削除を行う。
     *
     * @param Request $request 削除リクエスト
     * @param string $id 都市モデルID
     *
     * @return
     */
    public function destroy(Request $request, string $id)
    {
        try {

            $isDeleteFlg = $request->query->get('delete_flg');
            if ($isDeleteFlg) {

                DB::beginTransaction();
                // 都市モデル参照権限と都市モデルを削除
                $deleteResult = CityModelService::deleteCityModelById($id);
                if ($deleteResult['result']) {
                    DB::commit();
                    foreach ($deleteResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('city_model.index');
                } else {
                    throw new Exception("都市モデル削除に失敗しました。都市モデルID: {$id}");
                }
            } else {

                $errorMessage = [];

                // 登録ユーザ
                $registeredUserId = $request->query->get('registered_user_id');

                // 削除操作ができるか確認
                if ($id == 0) {
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                } else if (!self::isLoginUser($registeredUserId)) {
                    $errorMessage = ["type" => "E", "code" => "E3", "msg" => Message::$E3];
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('city_model.index')->with(['message' => $errorMessage]);
                } else {
                    $identificationName = CityModelService::getCityModelById($id)->identification_name;
                    $warningMessage = ["type" => "W", "code" => "W1", "msg" => sprintf(Message::$W1, $identificationName)];
                    return redirect()->route('city_model.index')->with(['message' => $warningMessage, 'citymodelId' => $id]);
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 都市モデルの共有
     * @param Request $request リクエスト
     * @param string $id 都市モデルID
     *
     * @return
     */
    public function share(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            $cityModel = null;

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 編集操作ができるか確認
            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else if (!self::isLoginUser($registeredUserId)) {
                $errorMessage = ["type" => "E", "code" => "E3", "msg" => Message::$E3];
            } else {
                $cityModel = CityModelService::getCityModelById($id);
                if ($cityModel->preset_flag) {
                    // プリセットフラグが有効の場合、[E8]エラー
                    $errorMessage = ["type" => "E", "code" => "E8", "msg" => Message::$E8];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.index')->with(['message' => $errorMessage]);
            } else {
                return redirect()->route('share.index')->with(['share_mode' => Constants::SHARE_MODE_CITY_MODEL, 'model' => $cityModel]);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 3D都市モデルに紐づく解析対象地域を取得する
     * @param Request $request リクエスト
     *
     *
     * @return
     */
    public function getRegionsByCityModelId(Request $request)
    {
        try {
            $cityModelId = $request->get('city_model_id');
            $regions = [];
            if ($cityModelId != 0) {
                $cityModel = CityModelService::getCityModelById($cityModelId);
                $regions = $cityModel->regions()->get();
            }
            return response()->json(['regions' => $regions]);
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }
}
