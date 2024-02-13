<?php

namespace App\Http\Controllers;

use App\Commons\Message;
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
                $addNewResult = RegionService::addNewOrUpdateRegion($request, $city_model_id);
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
     * 解析対象地域の更新
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function update(Request $request, string $city_model_id, string $region_id)
    {
        try {
            $errorMessage = [];

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 削除操作ができるか確認
            if ($region_id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_delete' => 1])->with(['message' => $errorMessage]);
            } else {

                // 解析対象地域の更新
                DB::beginTransaction();
                $updateResult = RegionService::addNewOrUpdateRegion($request, $city_model_id, $region_id);
                if ($updateResult['result']) {
                    DB::commit();
                    foreach ($updateResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId]);
                } else {
                    throw new Exception("解析対象地域の更新に失敗しました。解析対象地域ID: {$region_id}");
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
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function destroy(Request $request, string $city_model_id, string $region_id)
    {
        try {
            $errorMessage = [];

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 削除操作ができるか確認
            if ($region_id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_delete' => 1])->with(['message' => $errorMessage]);
            } else {

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

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            if (!$region_id) {
                // 解析対象地域が未選択
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else if (!$stlFileRq) {
                // ソルバ一式圧縮ファイルが未選択
                $errorMessage = ["type" => "E", "code" => "E11", "msg" => Message::$E11];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId])->with(['message' => $errorMessage]);
            } else {

                // STLファイルのアップロード処理
                DB::beginTransaction();
                $addOrUpdateResutl = RegionService::addNewOrUpdateStlFile($city_model_id, $region_id, $stl_type_id, $stlFileRq);
                if ($addOrUpdateResutl['result']) {
                    DB::commit();
                    foreach ($addOrUpdateResutl['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
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

                // STL定義上下限
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

                $stlFileInfos = ['region' => $region, 'paritalViewStlFile' => $paritalViewStlFile, 'paritalViewStlDefinition' => $paritalViewStlDefinition];

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
     * @param Request $request リクエスト
     * @param string $city_model_id 都市モデルID
     * @param string $region_id 解析対象地域ID
     *
     * @return
     */
    public function destroyStlFile(Request $request, string $city_model_id, string $region_id)
    {
        try {
            $errorMessage = [];

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // STLファイル種別ID
            $stlTypeId = $request->query->get('stl_type_id');

            // 削除操作ができるか確認
            if (!$stlTypeId) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('city_model.edit', ['id' => $city_model_id, 'registered_user_id' => $registeredUserId, 'region_delete' => 1])->with(['message' => $errorMessage]);
            } else {

                //STLファイルの削除
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
            }
        } catch (Exception $e) {
            DB::rollback();
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }
}
