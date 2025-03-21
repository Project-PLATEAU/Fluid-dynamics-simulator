<?php

namespace App\Http\Controllers;

use App\Commons\Constants;
use App\Commons\Message;
use App\Services\ApiService;
use App\Services\RegionService;
use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

/**
 * 解析対象地域用のコントロール
 */
class RegionController extends BaseController
{
    /**
     * 解析対象地域の新規追加
     *
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     *
     * @return
     */
    public function store(Request $request, string $city_model_id)
    {
        try {
            $errorMessage = [];

            // 入力した対象地域識別名
            $regionName = $request->region_name;

            // 選択した平面角直角座標系
            $coordinate = $request->coordinate_id;

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 新規作成の操作ができるか確認
            if (!$regionName) {
                $errorMessage = ["type" => "E", "code" => "E9", "msg" => Message::$E9];
            } else {
                if (!$coordinate) {
                    $errorMessage = ["type" => "E", "code" => "E15", "msg" => Message::$E15];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId])->with(['message' => $errorMessage]);
            } else {

                // 解析対象地域の新規追加
                DB::beginTransaction();
                // ログインユーザ
                $loginUserId = self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id;
                $addNewResult = RegionService::addNewOrUpdateRegion($request, $city_model_id, "", $loginUserId);
                if ($addNewResult['result']) {
                    DB::commit();
                    foreach ($addNewResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId]);
                } else {
                    throw new Exception("対象地域識の新規作成に失敗しました。city_model_id: {$city_model_id}");
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * 解析対象地域の複製
     *
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     * @param string $region_id 複製元の解析対象地域ID
     *
     * @return
     */
    public function copy(Request $request, string $city_model_id, string $region_id)
    {
        try {

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 複製先の識別名
            $replicateToRegionName = $request->query->get('replicate_to_region_name');
            $copyFlg = $request->query->get('copy_flg');

            if ($copyFlg && $replicateToRegionName) {

                DB::beginTransaction();
                $loginUserId = self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id;

                // 解析対象地域の複製
                //  ・解析対象地域テーブルを複製
                //  ・ファイルストレージ内のOBJ / STLファイルも新規の解析対象地域IDフォルダ以下にOBJ / STLファイル種別ごとにコピーする。
                //  ・STLファイルテーブルを複製
                $copyResult = RegionService::copyRegion($city_model_id, $region_id, $replicateToRegionName, $loginUserId);
                if ($copyResult['result']) {
                    DB::commit();
                    foreach ($copyResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId]);
                } else {
                    throw new Exception("解析対象地域の複製に失敗しました。複製先の解析対象地域ID: {$region_id}");
                }
            } else {

                $errorMessage = [];

                // 複製操作ができるか確認
                if ($region_id == 0) {
                    // E2チェック
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                } else {
                    // E9チェック
                    if ($copyFlg) {
                        $errorMessage = ["type" => "E", "code" => "E9", "msg" => Message::$E9];
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_id' => $region_id])->with(['message' => $errorMessage]);
                } else {
                    // 複製ダイアログを表示する。
                    $showCopyDialog = ["type" => "C", "code" => "解析対象地域　複製", "msg" => "解析対象地域名 を入力してください。"];
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_id' => $region_id])->with(['message' => $showCopyDialog]);
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
     * 解析対象地域の削除
     *
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function destroy(Request $request, string $city_model_id, string $region_id)
    {
        try {

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            $isDeleteFlg = $request->query->get('delete_flg');
            if ($isDeleteFlg) {
                // 解析対象地域の削除
                DB::beginTransaction();
                $deleteResult = RegionService::deleteRegionById($city_model_id, $region_id);
                if ($deleteResult['result']) {
                    DB::commit();
                    foreach ($deleteResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId]);
                } else {
                    throw new Exception("解析対象地域の削除に失敗しました。解析対象地域ID: {$region_id}");
                }
            } else {

                $errorMessage = [];

                // 削除操作ができるか確認
                if ($region_id == 0) {
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                } else {
                    $region = RegionService::getRegionById($region_id);
                    if ($region) {
                        $regionUserId = $region->user_id;
                        if (!self::isLoginUser($regionUserId)) {
                            $errorMessage = ["type" => "E", "code" => "E3", "msg" => Message::$E3];
                        }
                    } else {
                        throw new Exception("解析対象地域の削除に失敗しました。解析対象地域ID: {$region_id}が存在しません。");
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_delete' => 1])->with(['message' => $errorMessage]);
                } else {
                    $region = RegionService::getRegionById($region_id);
                    if (!$region) {
                        throw new Exception("解析対象地域の削除に失敗しました。解析対象地域ID「{$region_id}」のレコードが存在しません。");
                    } else {
                        $warningMessage = ["type" => "W", "code" => "W1", "msg" => sprintf(Message::$W1, $region->region_name)];
                        return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_id' => $region_id])->with(['message' => $warningMessage]);
                    }
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
     * STLファイルのアップロード
     *
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function uploadStlFile(Request $request, string $city_model_id, string $region_id)
    {
        try {

            $errorMessage = [];

            // アップロードSTLファイル
            $stlFileRq = $request->file('stl_file');

            // STLファイル種別
            $stl_type_id = $request->stl_type_id;

            // 日射吸収率
            $solar_absorptivity = $request->solar_absorptivity;

            // 排熱量
            $heat_removal = $request->heat_removal;

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            if (!$region_id) {
                // 解析対象地域が未選択
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else if (!$stlFileRq) {
                // OBJファイルまたはSTLファイルが未選択
                $errorMessage = ["type" => "E", "code" => "E11", "msg" => Message::$E11];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId])->with(['message' => $errorMessage]);
            } else {

                // STLファイルのアップロード処理
                DB::beginTransaction();
                $addOrUpdateResutl = RegionService::addNewOrUpdateStlFile($city_model_id, $region_id, $stl_type_id, $stlFileRq, $solar_absorptivity, $heat_removal);
                if ($addOrUpdateResutl['result']) {
                    DB::commit();
                    foreach ($addOrUpdateResutl['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }

                    // ========「【IF204】3D都市モデルCZML変換API」を呼び出す。==========================
                    $rqParams = [
                        "region_id" => $region_id,
                        "stl_type_id" => $stl_type_id
                    ];
                    $apiStatusCode = ApiService::callConvertToCzmlAPI($rqParams);
                    LogUtil::i("CZML変換APIを呼び出しました。");
                    if (ApiService::isError($apiStatusCode)) {
                        $errorMessage = ["type" => "E", "code" => "E34", "msg" => Message::$E34];
                        LogUtil::w($errorMessage["msg"]);
                        return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_id' => $region_id])->with(['message' => $errorMessage]);
                    }
                    // ========「【IF204】3D都市モデルCZML変換API」を呼び出す。//==========================
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_id' => $region_id]);
                } else {
                    throw new Exception("STLファイルのアップロードに失敗しました。都市モデル: {$city_model_id}, 解析対象地域ID: {$region_id}");
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
     * 解析対象地域により、STL関連情報を更新
     *
     * @param Request $request リクエスト
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function updateStlInfo(Request $request, string $region_id)
    {
        try {

            $stlFileInfos = [];
            if ($region_id) {

                // 解析対象地域
                $region = RegionService::getRegionById($region_id);
                // STLファイル
                $stlFiles = $region->stl_models()->get();

                // STLファイル一覧のhtmlデータ
                $paritalViewStlFile = View::make('city_model.partial_stl.partial_stl_list', [
                    'stlFiles' => $stlFiles
                ])->render();

                // STL定義上下限のhtmlデータ
                $paritalViewStlDefinition = View::make('city_model.partial_stl.parital_stl_definition', [
                        'region' => $region
                    ])->render();

                // 3D地図表示のため、特定の解析対象地域に紐づいていたczmlファイルを取得する。
                // 「解析対象地域ID」に紐づいた複数の「STLファイル種別ID」ですが、すべてczmlファイル表示対象となります
                $czmlFiles = RegionService::getCzmlFiles($stlFiles);

                // 選択した解析対象地域を編集できるかどうかの状況を取得する。
                $regionEditIsOK = RegionService::editIsOK($region_id);

                // 解析対象地域一覧で選択された地域の登録者がログインユーザでない場合は非アクティブとする
                $buildingEditIsOk = true;
                $regionUserId = $region->user_id;
                if (!self::isLoginUser($regionUserId)) {
                    $buildingEditIsOk = false;
                }

                $stlFileInfos = ['region' => $region, 'czmlFiles' => $czmlFiles, 'regionEditIsOK' => $regionEditIsOK, 'buildingEditIsOk' => $buildingEditIsOk, 'paritalViewStlFile' => $paritalViewStlFile, 'paritalViewStlDefinition' => $paritalViewStlDefinition];

            } else {
                throw new Exception("解析対象地域により、STL関連情報更新に失敗しました。解析対象地域ID: {$region_id}が不正です。");
            }

            return response()->json($stlFileInfos);
        } catch(Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }


    /**
     * STLファイルを削除
     *
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function destroyStlFile(Request $request, string $city_model_id, string $region_id)
    {
        try {

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // STLファイル種別ID
            $stlTypeId = $request->query->get('stl_type_id');

            // 削除実行前確認ダイアログでOKボタンを押した。
            $isDeleteFlg = $request->query->get('delete_flg');
            if ($isDeleteFlg) {
                // STLファイルの削除
                DB::beginTransaction();
                $deleteResult = RegionService::deleteStlFile($city_model_id, $region_id, $stlTypeId);
                if ($deleteResult['result']) {
                    DB::commit();
                    foreach ($deleteResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_id' => $region_id]);
                } else {
                    throw new Exception("STLファイルの削除に失敗しました。解析対象地域ID: {$region_id}, STLファイル種別ID: {$$stlTypeId}");
                }
            } else {

                $errorMessage = [];

                // 削除操作ができるか確認
                if (!$stlTypeId) {
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_delete' => 1])->with(['message' => $errorMessage]);
                } else {
                    $sltModel = RegionService::getSltFile($region_id, $stlTypeId);
                    if (!$sltModel) {
                        throw new Exception("STLファイルの削除に失敗しました。解析対象地域ID「{$region_id}」とSTLファイル種別ID「{$stlTypeId}」の STLファイルレコードが存在しません。");
                    } else {
                        $warningMessage = ["type" => "W", "code" => "W1", "msg" => sprintf(Message::$W1, $sltModel->stl_type->stl_type_name)];
                        return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_id' => $region_id, 'stl_type_id' => $stlTypeId])->with(['message' => $warningMessage]);
                    }
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
     * 特定のSTLファイル種別によりSTLファイル種別情報を更新
     *
     * @param Request $request STLファイル種別情報更新のリクエスト
     *
     * @return
     */
    public function onChangeStlType(Request $request)
    {
        try {

            $stlTypeInfos = [];

            // 特定のSTLファイル種別ID
            $stl_type_id = $request->stl_type_id;
            if ($stl_type_id) {
                $stlType = RegionService::getStlType($stl_type_id);
                if (!$stlType) {
                    throw new Exception("特定のSTLファイル種別によりSTLファイル種別情報の更新に失敗しました。STLファイル種別IDが{$stl_type_id}のレコードが登録されていないようです。");
                }
                $stlTypeInfos = ['stl_type' => $stlType];
            } else {
                throw new Exception("特定のSTLファイル種別によりSTLファイル種別情報の更新に失敗しました。STLファイル種別ID: [{$stl_type_id}] が不正です。");
            }
            return response()->json($stlTypeInfos);
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }

    /**
     * 3D地図描画に必要な全てCZMLファイルが出来上がったか定期的に確認する。(ロングポーリング)
     *
     * @param Request $request 3D地図
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function longPollingWaitCzmlFile(Request $request, string $region_id)
    {
        try {

            $responseData = [];
            if ($region_id) {

                // ロングポーリングリクエストに応じて、3D地図描画に必要な全てCZMLファイルを取得
                $responseData = RegionService::waitCzmlFile($region_id);
            } else {
                throw new Exception("解析対象地域により、STL関連情報更新に失敗しました。解析対象地域ID: {$region_id}が不正です。");
            }

            return response()->json($responseData);
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }
}
