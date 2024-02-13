<?php

namespace App\Http\Controllers;

use App\Commons\Constants;
use App\Commons\Message;
use App\Services\CityModelService;
use App\Services\HeightService;
use App\Services\RegionService;
use App\Services\SimulationModelService;
use App\Services\SolverService;
use App\Utils\DatetimeUtil;
use App\Utils\FileUtil;
use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * シミュレーションモデル関連画面用のコントロール
 */
class SimulationModelController extends BaseController
{
    /**
     * シミュレーションモデル一覧画面の初期表示
     */
    public function index()
    {
        try {
            $simulationModelList = SimulationModelService::getSimulationModelList(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id);

            // 他画面からのリダイレクトで渡されたデータを受け取る。
            $message = session('message') ? session('message') : null;
            $simulationModelId = session('simulationModelId') ? session('simulationModelId') : null;
            return view('simulation_model.index', compact('simulationModelList', 'message', 'simulationModelId'));
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーションモデルを複製
     * @param Request $request リクエスト
     * @param string $id 複製元のシミュレーションモデルID
     *
     * @return
     */
    public function copy(Request $request, $id)
    {
        try {
            $errorMessage = [];

            if ($id == 0) {
                // 表示対象のシミュレーションモデルが未選択
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
            } else {
                // 複製処理：シミュレーションモデルテーブルと日射吸収率テーブルのレコード複製を行う。
                DB::beginTransaction();
                $copyResult = SimulationModelService::copySimulationModel(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id, $id);
                if ($copyResult['result']) {
                    DB::commit();
                    foreach ($copyResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('simulation_model.index');
                } else {
                    throw new Exception("シミュレーションモデル複製に失敗しました。複製元のシミュレーションモデルID: {$id}");
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
     * シミュレーションモデル追加画面を表示する。
     * @param mixed $city_model_id 都市モデルID
     *
     * @return
     */
    public function create($city_model_id)
    {
        try {

            if ($city_model_id) {
                $message = session('message');

                // 都市モデル
                $cityModel = CityModelService::getCityModelById($city_model_id);
                return view('simulation_model.create', compact('message', 'cityModel'));
            } else {
                throw new Exception("都市モデルIDが不正. 都市モデルID: {$city_model_id}");
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーションモデルを新規登録
     * @param Request $request リクエスト
     * @param mixed $city_model_id 都市モデルID
     *
     * @return
     */
    public function store(Request $request, $city_model_id)
    {
        try {

            if ($city_model_id) {

                $errorMessage = [];

                // 登録ユーザ
                $registeredUserId = self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id;

                // モデル識別名
                $identification_name = $request->get('identification_name');

                // 選択した解析対象地域
                $regionId = $request->query->get('region_id');
                $region = null;

                // 新規登録の操作ができるか確認
                if (!$identification_name) {
                    $errorMessage = ["type" => "E", "code" => "E9", "msg" => Message::$E9];
                } else if (!$regionId) {
                    $errorMessage = ["type" => "E", "code" => "E16", "msg" => Message::$E16];
                } else {
                    $region = RegionService::getRegionById($regionId);
                    $stlModels = $region->stl_models()->get();
                    $isE18 = true;
                    foreach ($stlModels as $stlModel) {
                        if ($stlModel->stl_type->ground_flag) {
                            $isE18 = false;
                            break;
                        }
                    }
                    if ($isE18) {
                        $errorMessage = ["type" => "E", "code" => "E18", "msg" => sprintf(Message::$E18, $region->region_name)];
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('simulation_model.create', ['city_model_id' => $city_model_id])->with(['message' => $errorMessage]);
                } else {

                    // 新規登録の処理
                    DB::beginTransaction();
                    $addNewResult = SimulationModelService::addNewSimulation($identification_name, $city_model_id, $region, $registeredUserId);
                    if ($addNewResult['result']) {
                        DB::commit();
                        foreach ($addNewResult['log_infos'] as $key => $log) {
                            LogUtil::i($log);
                        }
                        $simulationModel = $addNewResult['simulation_model'];
                        return redirect()->route('simulation_model.edit', ['id' => $simulationModel->simulation_model_id]);
                    } else {
                        throw new Exception("シミュレーションモデルの新規登録に失敗しました。識別名: {$identification_name}, 都市モデルID: {$city_model_id}");
                    }
                }
            } else {
                throw new Exception("都市モデルIDが不正. 都市モデルID: {$city_model_id}");
            }
        } catch (Exception $e) {
            DB::rollBack();
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーションモデル編集画面を表示
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function edit(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            // 登録ユーザ
            //  シミュレーションモデル編集画面より遷移した場合: 登録ユーザは$request->query->get('registered_user_id')より取得
            //  シミュレーションモデル作成後に遷移した場合 :登録ユーザはログイン中のユーザとする。
            $registeredUserId = $request->query->get('registered_user_id') ? $request->query->get('registered_user_id') : self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id;

            // 編集操作ができるか確認
            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else if (!self::isLoginUser($registeredUserId)) {
                $errorMessage = ["type" => "E", "code" => "E3", "msg" => Message::$E3];
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
            } else {
                $simulationModel = SimulationModelService::getSimulationModelById($id);

                $solverList = SolverService::getAllSolver($registeredUserId);

                // 更新処理に失敗時のエラー
                $message = session('message');
                if (!$message) {
                    // 選択行のSM27実行ステータスが1(開始処理中)、2(実行中)、3(正常終了)のいずれかであれば、画面遷移後にメッセージコード「I5」を表示する。
                    if (($simulationModel->run_status == Constants::RUN_STATUS_CODE_START_PROCESSING) ||
                        ($simulationModel->run_status == Constants::RUN_STATUS_CODE_RUNNING) ||
                        ($simulationModel->run_status == Constants::RUN_STATUS_CODE_NORMAL_END)) {
                            $message = ["type" => "I", "code" => "I5", "msg" => Message::$I5];
                    }
                }

                return view('simulation_model.edit', compact('simulationModel', 'solverList', 'registeredUserId', 'message'));
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーションモデルを更新
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function update(Request $request, string $id)
    {
        try {

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 編集操作ができるか確認 E9, E21, E23, E24, E25, E19, E20, E5
            $errorMessage = SimulationModelService::isUpdate($request, $id);

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.edit', ['id' => $id, 'registered_user_id' => $registeredUserId])->with(['message' => $errorMessage]);
            } else {

                DB::beginTransaction();
                $updateResult = SimulationModelService::updateSimulation($request, $id);
                if ($updateResult['result']) {
                    DB::commit();
                    foreach ($updateResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('simulation_model.index');
                } else {
                    throw new Exception("シミュレーションモデルの更新に失敗しました。シミュレーションモデルID: {$id}");
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
     * シミュレーションモデルを削除
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function destroy(Request $request, string $id)
    {
        try {

            $isDeleteFlg = $request->query->get('delete_flg');
            if ($isDeleteFlg) {

                DB::beginTransaction();
                // シミュレーションモデルテーブルと日射吸収率テーブルとシミュレーションモデル参照権限テーブルのレコードを削除
                $deleteResult = SimulationModelService::deleteSimulationModelById($id);
                if ($deleteResult['result']) {
                    DB::commit();
                    foreach ($deleteResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }
                    return redirect()->route('simulation_model.index');
                } else {
                    throw new Exception("シミュレーションモデルの削除に失敗しました。シミュレーションモデルID: {$id}");
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
                    return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
                } else {
                    $identificationName = SimulationModelService::getSimulationModelById($id)->identification_name;
                    $warningMessage = ["type" => "W", "code" => "W1", "msg" => sprintf(Message::$W1, $identificationName)];
                    return redirect()->route('simulation_model.index')->with(['message' => $warningMessage, 'simulationModelId' => $id]);
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
     * シミュレーションモデルの共有
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function share(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            $simulationModel = null;

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 編集操作ができるか確認
            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else if (!self::isLoginUser($registeredUserId)) {
                $errorMessage = ["type" => "E", "code" => "E3", "msg" => Message::$E3];
            } else {
                $simulationModel = SimulationModelService::getSimulationModelById($id);
                if ($simulationModel->preset_flag) {
                    // プリセットフラグが有効の場合、[E8]エラー
                    $errorMessage = ["type" => "E", "code" => "E8", "msg" => Message::$E8];
                } else if (!$simulationModel->solver->disclosure_flag) {
                    //  熱流体解析ソルバの公開フラグが無効の場合、「E28」
                    $solverName = $simulationModel->solver->solver_name;
                    $errorMessage = ["type" => "E", "code" => "E28", "msg" => sprintf(Message::$E28, $solverName)];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
            } else {
                return redirect()->route('share.index')->with(['share_mode' => Constants::SHARE_MODE_SIMULATION_MODEL, 'model' => $simulationModel]);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーションモデル開始
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function start(Request $request, string $id)
    {
        try {

            $isStopFlg = $request->query->get('stop_flg');
            if ($isStopFlg) {

                // シミュレーションモデルテーブルの実行ステータス「5 中止処理中」更新
                if (SimulationModelService::updateSimulationById($id, 'run_status', Constants::RUN_STATUS_CODE_CANCEL_PROCESSING)) {
                    LogUtil::i("[simulation_model] [update] [simulation_model_id: {$id}, run_status: 5]");
                    $infoMessage = ["type" => "I", "code" => "I2", "msg" => Message::$I2];
                    return redirect()->route('simulation_model.index')->with(['message' => $infoMessage]);
                } else {
                    throw new Exception("シミュレーションモデル開始 で「5 中止処理中」更新に失敗しました。シミュレーションモデルID: {$id}");
                }

            } else {
                $errorMessage = [];

                $simulationModel = null;

                // 登録ユーザ
                $registeredUserId = $request->query->get('registered_user_id');

                // 編集操作ができるか確認
                if ($id == 0) {
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                } else if (!self::isLoginUser($registeredUserId)) {
                    $errorMessage = ["type" => "E", "code" => "E3", "msg" => Message::$E3];
                } else {

                    $simulationModel = SimulationModelService::getSimulationModelById($id);
                    if (count($simulationModel->region->stl_models) == 0) {
                        $errorMessage = ["type" => "E", "code" => "E18", "msg" => sprintf(Message::$E18, $simulationModel->region->region_name)];
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
                } else {

                    $mixedMessage = [];

                    if ($simulationModel->run_status == Constants::RUN_STATUS_CODE_RUNNING) {
                        $mixedMessage = ["type" => "Q", "code" => "Q1", "msg" => sprintf(Message::$Q1, DatetimeUtil::changeFormat($simulationModel->last_sim_start_datetime))];
                    } else if ($simulationModel->run_status == Constants::RUN_STATUS_CODE_NORMAL_END) {
                        $mixedMessage = ["type" => "E", "code" => "E29", "msg" => Message::$E29];
                    } else if ($simulationModel->run_status == Constants::RUN_STATUS_CODE_START_PROCESSING || $simulationModel->run_status == Constants::RUN_STATUS_CODE_CANCEL_PROCESSING) {
                        $mixedMessage = ["type" => "E", "code" => "E5", "msg" => sprintf(Message::$E5, Constants::RUN_STATUS_NONE)];
                    }

                    // シミュレーション中止するかどうか
                    if ($mixedMessage) {
                        return redirect()->route('simulation_model.index')->with(['message' => $mixedMessage, 'simulationModelId' => $id]);
                    } else {
                        // シミュレーション開始処理
                        DB::beginTransaction();
                        $startResult = SimulationModelService::startSimulation($id);
                        if ($startResult['result']) {
                            DB::commit();

                            foreach ($startResult['log_infos'] as $key => $log) {
                                LogUtil::i($log);
                            }

                            $infoMessage = ["type" => "I", "code" => "I3", "msg" => Message::$I3];
                            return redirect()->route('simulation_model.index')->with(['message' => $infoMessage]);
                        } else {
                            throw new Exception("シミュレーション開始処理に失敗しました。シミュレーションモデルID: {$id}");
                        }
                    }
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
     * ステータス詳細
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function statusDetail(Request $request, string $id)
    {
        try {

            $isDownloadFlg = $request->query->get('download_flg');
            if ($isDownloadFlg) {
                $simulationModel = SimulationModelService::getSimulationModelById($id);
                $cfdErrorLogFile =  $simulationModel ?  $simulationModel->cfd_error_log_file : "";
                if ($cfdErrorLogFile && FileUtil::isExists($cfdErrorLogFile)) {
                    return FileUtil::download($cfdErrorLogFile, FileUtil::LOG_ZIP_FILE);
                } else {
                    throw new Exception("熱流体解析エラーログファイルが存在しません。{$cfdErrorLogFile}");
                }
            } else {

                $errorMessage = [];

                $simulationModel = null;

                // 登録ユーザ
                $registeredUserId = $request->query->get('registered_user_id');

                // 編集操作ができるか確認
                if ($id == 0) {
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                } else {

                    $simulationModel = SimulationModelService::getSimulationModelById($id);
                    if (!$simulationModel->run_status_details) {
                        // SM28実行ステータス詳細が空欄ならE4を出す
                        $errorMessage = ["type" => "E", "code" => "E4", "msg" =>Message::$E4];
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
                } else {
                    $infoOrQuestionMessage = [];
                    if ($simulationModel->cfd_error_log_file) {
                        $msg = sprintf(
                            Message::$Q2,
                            DatetimeUtil::changeFormat($simulationModel->last_sim_start_datetime),
                            $simulationModel->getRunStatusName(),
                            $simulationModel->run_status_details
                        );
                        $infoOrQuestionMessage = ["type" => "Q", "code" => "Q2", "msg" => $msg];
                    } else {
                        $msg = sprintf(
                            Message::$I1,
                            DatetimeUtil::changeFormat($simulationModel->last_sim_start_datetime),
                            $simulationModel->getRunStatusName(),
                            $simulationModel->run_status_details
                        );
                        $infoOrQuestionMessage = ["type" => "I", "code" => "I1", "msg" => $msg];
                    }
                    return redirect()->route('simulation_model.index')->with(['message' => $infoOrQuestionMessage, 'simulationModelId' => $id]);
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーションモデル開始
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function stop(Request $request, string $id)
    {
        try {
            $isStopFlg = $request->query->get('stop_flg');
            if ($isStopFlg) {

                // シミュレーション開中止処理
                DB::beginTransaction();
                $stopResult = SimulationModelService::stopSimulation($id);
                if ($stopResult['result']) {
                    DB::commit();

                    foreach ($stopResult['log_infos'] as $key => $log) {
                        LogUtil::i($log);
                    }

                    $infoMessage = ["type" => "I", "code" => "I2", "msg" => Message::$I2];
                    return redirect()->route('simulation_model.index')->with(['message' => $infoMessage]);
                } else {
                    throw new Exception("シミュレーション中止処理に失敗しました。シミュレーションモデルID: {$id}");
                }
            } else {

                $errorMessage = [];

                $simulationModel = null;

                // 登録ユーザ
                $registeredUserId = $request->query->get('registered_user_id');

                // 編集操作ができるか確認
                if ($id == 0) {
                    $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                } else if (!self::isLoginUser($registeredUserId)) {
                    $errorMessage = ["type" => "E", "code" => "E3", "msg" => Message::$E3];
                } else {

                    $simulationModel = SimulationModelService::getSimulationModelById($id);
                    if ($simulationModel->run_status != Constants::RUN_STATUS_CODE_RUNNING) {
                        $errorMessage = ["type" => "E", "code" => "E5", "msg" => sprintf(Message::$E5, Constants::RUN_STATUS_RUNNING)];
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
                } else {

                    // シミュレーション中止するかどうか
                    $warningMessage = ["type" => "W", "code" => "W3", "msg" => sprintf(Message::$W3, DatetimeUtil::changeFormat($simulationModel->last_sim_start_datetime))];
                    return redirect()->route('simulation_model.index')->with(['message' => $warningMessage, 'simulationModelId' => $id]);
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
     * シミュレーション結果閲覧
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function show(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            $simulationModel = null;

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 編集操作ができるか確認
            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else {
                $simulationModel = SimulationModelService::getSimulationModelById($id);
                if ($simulationModel->run_status != Constants::RUN_STATUS_CODE_NORMAL_END) {
                    $errorMessage = ["type" => "E", "code" => "E5", "msg" => sprintf(Message::$E5, Constants::RUN_STATUS_NORMAL_END)];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
            } else {

                // 高さを取得
                $heightList = HeightService::getAll();

                //可視化種別：風況（※デフォルト）
                $visualizationType = Constants::VISUALIZATION_TYPE_WINDY;

                // 高さ:1.5m（※デフォルト）
                $heightId = $request->query->get('height') ? $request->query->get('height') : $heightList->toArray()[0]['height_id'];

                // 可視化ファイル
                $visualization = SimulationModelService::getVisualization($id, $visualizationType, $heightId);

                return view('simulation_model.view', compact('simulationModel', 'heightList', 'visualizationType', 'heightId', 'visualization'));
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーション結果閲覧(可視化種別により表示)
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function changeShowMode(Request $request, string $id)
    {
        try {

            // 高さを取得
            $heightList = HeightService::getAll();

            //可視化種別
            $visualizationType = $request->query->get('visualization_type');
            // 高さ
            $heightId = $request->query->get('height');

            // 表示モードを切り替え前の状態（方向、ピッチ、ポジション）を取得
            $mapCurrentHeading = $request->query->get('map_current_heading');
            $mapCurrentPitch = $request->query->get('map_current_pitch');
            $mapCurrentRoll = $request->query->get('map_current_roll');
            $mapCurrentPositionX = $request->query->get('map_current_position_x');
            $mapCurrentPositionY = $request->query->get('map_current_position_y');
            $mapCurrentPositionZ = $request->query->get('map_current_position_z');

            if ($id && $visualizationType) {
                if (($visualizationType == Constants::VISUALIZATION_TYPE_WINDY ||
                    $visualizationType == Constants::VISUALIZATION_TYPE_TEMP) && !$heightId) {
                    throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}, 高さ: null");
                }
            } else {
                throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}");
            }

            $simulationModel = SimulationModelService::getSimulationModelById($id);
            // 可視化ファイル
            $visualization = SimulationModelService::getVisualization($id, $visualizationType, $heightId);
            if ($visualization) {
                return view('simulation_model.view', compact(
                    'simulationModel', 'heightList', 'visualizationType', 'heightId', 'visualization',
                    'mapCurrentHeading', 'mapCurrentPitch', 'mapCurrentRoll', 'mapCurrentPositionX',
                    'mapCurrentPositionY', 'mapCurrentPositionZ'));
            } else {
                throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}, 高さ: {$heightId} の可視化ファイルが見つかりませんでした。");
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーション結果（GeoJSON）ファイルをダウンロード
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function download(Request $request, string $id)
    {
        try {

            // 選択したラジオボタン
            $visualizationType = $request->query->get('visualization_type');
            // 選択した高さ
            $heightId = $request->query->get('height');

            // 可視化ファイル
            $visualization = SimulationModelService::getVisualization($id, $visualizationType, $heightId);

            if (!$visualization) {
                throw new Exception("指定した条件「シミュレーションモデルID： {$id}、可視化種別: {$visualizationType}、高さID：{$heightId}」に当てはまる「可視化ファイル」が存在しません。");
            } else {
                $geojsonFilePath = $visualization->geojson_file;

                if ($geojsonFilePath && FileUtil::isExists($geojsonFilePath)) {
                    return FileUtil::download($geojsonFilePath);
                } else {
                    throw new Exception("指定したシミュレーション結果（GeoJSON）ファイルが存在しません。 {$geojsonFilePath}");
                }
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }
}
