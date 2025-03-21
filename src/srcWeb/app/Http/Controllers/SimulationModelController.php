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
use App\Utils\StringUtil;
use Exception;
use Faker\Core\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

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
     * @param Uuid $id 複製元のシミュレーションモデルID
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
     */
    public function create()
    {
        try {
            $message = session('message');
            $cityModelList = CityModelService::getCityModelList(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id);
            return view('simulation_model.create', compact('message', 'cityModelList'));
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

            $errorMessage = [];

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
                    if ($stlModel->stl_type->required_flag) {
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
                $cityModelList = CityModelService::getCityModelList(self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id);
                return redirect()->route('simulation_model.create', ['cityModelList' => $cityModelList])->with(['message' => $errorMessage]);
            } else {
                // 新規登録の処理
                DB::beginTransaction();
                $addNewResult = SimulationModelService::addNewSimulation($identification_name, $city_model_id, $region, self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id);
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

            // シミュレーションモデルにより、セッションキーが異なる。
            $smPolicySessionKey = Constants::SM_POLICY_SESSION_KEY . $id;
            // 実施施策一覧に行を追加や削除の際に一時的に設定したセッションデータを削除
            $request->session()->forget($smPolicySessionKey);

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

                // 熱対策施策の各レコード
                $policyList = SimulationModelService::getAllPolicy();

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

                // 風向表示設定ファイル(json)を読み込んで、プルダウン表示ようのデータを作成
                $windDirections = SimulationModelService::createWindirectionDropdown();

                return view('simulation_model.edit', compact('simulationModel', 'solverList', 'registeredUserId', 'message', 'policyList', 'windDirections'));
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

                    // シミュレーションモデルにより、セッションキーが異なる。
                    $smPolicySessionKey = Constants::SM_POLICY_SESSION_KEY . $id;
                    // 実施施策一覧に行を追加や削除の際に一時的に設定したセッションデータを削除
                    $request->session()->forget($smPolicySessionKey);

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
     * @param Uuid $id シミュレーションモデルID
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
     * シミュレーションモデルの公開
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function publish(Request $request, string $id)
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
                if (!$simulationModel) {
                    throw new Exception("シミュレーションモデルの公開に失敗しました。シミュレーションモデルID「{$id}」のレコードが存在しません。");
                } else if ($simulationModel->run_status != Constants::RUN_STATUS_CODE_NORMAL_END) {
                    // 実行ステータスがが正常終了でない場合、[E5]を表示する。
                    $errorMessage = ["type" => "E", "code" => "E5", "msg" => sprintf(Message::$E5, Constants::RUN_STATUS_NORMAL_END, $simulationModel->identification_name)];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
            } else {

                $infoMessage = [];
                // 公開用の閲覧URL
                $showURL = route('simulation_model.show_publish', ['id' => $id]);

                // 今の一般公開フラグが有効であれば、I7を表示する。
                if ($simulationModel->disclosure_flag) {
                    $infoMessage = ["type" => "I", "code" => "I7", "msg" => sprintf(Message::$I7, $showURL)];
                } else {
                    // 今の一般公開フラグが無効であれば、有効にして、I6を表示する。
                    $updateResult = SimulationModelService::updateSimulationById($id, 'disclosure_flag', true);
                    if ($updateResult) {
                        LogUtil::i("[simulation_model] [update] [simulation_model_id: {$id}, disclosure_flag: true]");
                        $infoMessage = ["type" => "I", "code" => "I6", "msg" => sprintf(Message::$I6, $showURL)];
                    } else {
                        throw new Exception("シミュレーションモデルID「{$id}」の公開に失敗しました。");
                    }
                }

                return redirect()->route('simulation_model.index')->with(['message' => $infoMessage]);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * シミュレーションモデルの公開停止
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function publishStop(Request $request, string $id)
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
                if (!$simulationModel) {
                    throw new Exception("シミュレーションモデルの公開停止に失敗しました。シミュレーションモデルID「{$id}」のレコードが存在しません。");
                } else if (!$simulationModel->disclosure_flag) {
                    // 今の一般公開フラグが無効であれば、「E30」を表示する。
                    $errorMessage = ["type" => "E", "code" => "E30", "msg" => Message::$E30];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
            } else {

                $infoMessage = [];

                // 一般公開フラグを無効にして、I8を表示する。
                $updateResult = SimulationModelService::updateSimulationById($id, 'disclosure_flag', false);
                if ($updateResult) {
                    LogUtil::i("[simulation_model] [update] [simulation_model_id: {$id}, disclosure_flag: false]");
                    $infoMessage = ["type" => "I", "code" => "I8", "msg" => Message::$I8];
                } else {
                    throw new Exception("シミュレーションモデルID「{$id}」の公開停止に失敗しました。");
                }

                return redirect()->route('simulation_model.index')->with(['message' => $infoMessage]);
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
                        $mixedMessage = ["type" => "E", "code" => "E5", "msg" => sprintf(Message::$E5, Constants::RUN_STATUS_NONE, $simulationModel->identification_name)];
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
                        $errorMessage = ["type" => "E", "code" => "E5", "msg" => sprintf(Message::$E5, Constants::RUN_STATUS_RUNNING, $simulationModel->identification_name)];
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
     * @param string $id シミュレーションモデルID(※対象が複数行の場合：カンマー区切りで分ける)
     *
     * @return
     */
    public function show(Request $request, string $id)
    {
        try {

            // シミュレーションモデルにより、セッションキーが異なる。
            $smPolicySessionKey = Constants::RECREATE_SM_POLICY_SESSION_KEY . $id;
            // 実施施策一覧に行を追加や削除の際に一時的に設定したセッションデータを削除
            $request->session()->forget($smPolicySessionKey);

            $errorMessage = [];

            $simulationModel = null;
            $simulationModelArr = [];

            // 登録ユーザ
            $registeredUserId = $request->query->get('registered_user_id');

            // 編集操作ができるか確認
            if ($id == 0) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
            } else {

                // 選択したシミュレーション配列
                $smIdArr = StringUtil::stringToArray($id);

                // 異常のシミュレーション配列
                $_errorSmIdentificationName = [];

                // 選択したシミュレーションに異常があったかチェックする。
                foreach ($smIdArr as $index => $_id) {

                    $simulationModel = SimulationModelService::getSimulationModelById($_id);
                    if (!$simulationModel) {
                        throw new Exception("シミュレーション結果閲覧に失敗しました。シミュレーションモデルID: 「{$_id}」のレコードが存在しません。");
                    } else if ($simulationModel->run_status != Constants::RUN_STATUS_CODE_NORMAL_END) {
                        $_errorSmIdentificationName[] = $simulationModel->identification_name;
                    } else {
                        $simulationModelArr[] = $simulationModel;
                    }
                }

                // 対象が複数行の場合、E5の｛1｝では、カンマ区切り等でシミュレーションモデル名を分けて表示する。
                if (count($_errorSmIdentificationName) > 0) {
                    $errorMessage = ["type" => "E", "code" => "E5", "msg" => sprintf(Message::$E5, Constants::RUN_STATUS_NORMAL_END, StringUtil::arrayToString($_errorSmIdentificationName))];
                }
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('simulation_model.index')->with(['message' => $errorMessage]);
            } else {

                // 高さを取得
                $heightList = HeightService::getAll();

                // 可視化種別：風況（※デフォルト）
                $visualizationType = Constants::VISUALIZATION_TYPE_WINDY;

                // 高さ:1.5m（※デフォルト）
                $heightId = $request->query->get('height') ? $request->query->get('height') : $heightList->toArray()[0]['height_id'];

                // シミュレーション結果閲覧画面表示に必要な情報
                $show_results = [];

                // シミュレーションモデルごとのczmlファイル(建物のデータ)と可視化ファイルを取得する。
                foreach ($simulationModelArr as $index => $sm) {

                    // czmlファイル(建物のデータ)
                    $czmlFiles = SimulationModelService::getCzmlFileWithoutNull($sm);

                    // 可視化ファイルを取得
                    $visualization = SimulationModelService::getVisualization($sm->simulation_model_id, $visualizationType, $heightId);
                    if (!$visualization) {
                        throw new Exception("シミュレーション結果閲覧に失敗しました。シミュレーションモデルID: 「{$_id}」の可視化ファイルが存在しません。");
                    } else {
                        if ($visualization->visualization_file) {
                            $czmlFiles[] = FileUtil::referenceStorageFile($visualization->visualization_file);
                        }
                    }

                    $show_results[] = [
                        "simulation_model" => $sm,
                        "visualization" => $visualization,
                        "czml_files" => $czmlFiles,
                        "visualization_type" => $visualizationType,
                        "height_id" => $heightId
                    ];
                }

                // 熱対策施策の各レコード
                $policyList = SimulationModelService::getAllPolicy();

                // 風向表示設定ファイル(json)を読み込んで、プルダウン表示ようのデータを作成
                $windDirections = SimulationModelService::createWindirectionDropdown();

                return view('simulation_model.view', compact('heightList', 'show_results', 'policyList', 'windDirections'));
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 公開用のシミュレーション結果閲覧
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function showPublish(Request $request, string $id)
    {
        try {
            $errorMessage = [];

            $simulationModel = SimulationModelService::getSimulationModelById($id);

            if (!$simulationModel) {
                throw new Exception("シミュレーション結果閲覧に失敗しました。「{$id}」のシミュレーションモデルが存在しません。");
            } else if (!$simulationModel->disclosure_flag) {
                // 閲覧用のシミュレーションモデルが公開されない場合はエラースルー
                throw new Exception("シミュレーション結果閲覧に失敗しました。{$id}」のシミュレーションモデルが一般公開されていません。");
            } else {

                // 高さを取得
                $heightList = HeightService::getAll();

                //可視化種別：風況（※デフォルト）
                $visualizationType = Constants::VISUALIZATION_TYPE_WINDY;

                // 高さ:1.5m（※デフォルト）
                $heightId = $request->query->get('height') ? $request->query->get('height') : $heightList->toArray()[0]['height_id'];

                // 可視化ファイル
                $visualization = SimulationModelService::getVisualization($id, $visualizationType, $heightId);
                if (!$visualization) {
                    throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}, 高さ: {$heightId} の可視化ファイルが見つかりませんでした。");
                } else {
                    return view('simulation_model.view_publish', compact('simulationModel', 'heightList', 'visualizationType', 'heightId', 'visualization'));
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error_publish', compact('e'));
        }
    }

    /**
     * シミュレーション結果閲覧(可視化種別により表示)
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID(※複数シミュレーションの場合：カンマー区切りで分ける)
     *
     * @return
     */
    public function changeShowMode(Request $request, string $id)
    {
        try {

            $simulationModelArr = [];

            // 選択したシミュレーション配列
            $smIdArr = StringUtil::stringToArray($id);

            // 念のため、シミュレーションモデルの存在チェックを行う。
            foreach ($smIdArr as $index => $_id) {
                $simulationModel = SimulationModelService::getSimulationModelById($_id);
                if (!$simulationModel) {
                    throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーション結果閲覧に失敗しました。「{$_id}」のシミュレーションモデルが存在しません。");
                } else {
                    $simulationModelArr[] = $simulationModel;
                }
            }

            // シミュレーション結果閲覧画面表示に必要な情報
            $show_results = [];

            // シミュレーションモデルごとのczmlファイル(建物のデータ)と可視化ファイルを取得する。
            foreach ($simulationModelArr as $index => $sm) {

                // シミュレーションモデルにより、セッションキーが異なる。
                $smPolicySessionKey = Constants::RECREATE_SM_POLICY_SESSION_KEY . $sm->simulation_model_id;
                // 実施施策一覧に行を追加や削除の際に一時的に設定したセッションデータを削除
                $request->session()->forget($smPolicySessionKey);

                // czmlファイル(建物のデータ)
                $czmlFiles = SimulationModelService::getCzmlFileWithoutNull($sm);

                // 地図ごとの可視化種別
                $visualizationType = $request->query->get('visualization_type_' . $index);

                // 地図ごとの高さ
                $heightId = $request->query->get('height_' . $index);

                // 固定値利用可否モード
                $legendType = ($request->query->get('ckb_value_fixed_' . $index) != null) ? $request->query->get('ckb_value_fixed_' . $index) : Constants::LEGENF_TYPE_FLUCTUATION;

                if ($visualizationType) {
                    // 可視化種別：1.風況と2.温度の場合、高さの指定が必要
                    // 可視化種別：3.暑さ指数の場合、高さの指定が不要。
                    if (($visualizationType == Constants::VISUALIZATION_TYPE_WINDY ||
                        $visualizationType == Constants::VISUALIZATION_TYPE_TEMP) && !$heightId) {
                        throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}, 高さ: null");
                    }

                    // 可視化種別：3.暑さ指数の場合、凡例種別を変動（1）にする。
                    // ※「暑さ指数」の場合は、固定値（デフォルトの最高・最低）利用ができないため。
                    if ($visualizationType == Constants::VISUALIZATION_TYPE_HEAT_INDEX) {
                        // 「デフォルトの最高・最低を使用する」チェック状況を無視にする。
                        $legendType = Constants::LEGENF_TYPE_FLUCTUATION;
                    }
                } else {
                    throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}");
                }

                // 可視化ファイルを取得
                $visualization = SimulationModelService::getVisualization($sm->simulation_model_id, $visualizationType, $heightId, $legendType);
                if (!$visualization) {
                    throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}, 高さ: {$heightId}, 凡例種別: {$legendType} の可視化ファイルが見つかりませんでした。");
                } else {
                    if ($visualization->visualization_file) {
                        $czmlFiles[] = FileUtil::referenceStorageFile($visualization->visualization_file);
                    }
                }

                // 表示モードを切り替え前の状態（方向、ピッチ、ポジション）を取得
                $mapCurrentHeading = $request->query->get('map_current_heading_'. $index);
                $mapCurrentPitch = $request->query->get('map_current_pitch_' . $index);
                $mapCurrentRoll = $request->query->get('map_current_roll_' . $index);
                $mapCurrentPositionX = $request->query->get('map_current_position_x_' . $index);
                $mapCurrentPositionY = $request->query->get('map_current_position_y_' . $index);
                $mapCurrentPositionZ = $request->query->get('map_current_position_z_' . $index);

                $show_results[] = [
                    "simulation_model" => $sm,
                    "visualization" => $visualization,
                    "czml_files" => $czmlFiles,
                    "visualization_type" => $visualizationType,
                    "height_id" => $heightId,
                    "ckb_value_fixed" => $legendType,
                    "viewer_camera" => [
                        "map_current_heading" => $mapCurrentHeading,
                        "map_current_pitch" =>  $mapCurrentPitch,
                        "map_current_roll" => $mapCurrentRoll,
                        "map_current_position_x" => $mapCurrentPositionX,
                        "map_current_position_y" => $mapCurrentPositionY,
                        "map_current_position_z" => $mapCurrentPositionZ
                    ]
                ];
            }

            // 高さを取得
            $heightList = HeightService::getAll();

            // 熱対策施策の各レコード
            $policyList = SimulationModelService::getAllPolicy();

            // 風向表示設定ファイル(json)を読み込んで、プルダウン表示ようのデータを作成
            $windDirections = SimulationModelService::createWindirectionDropdown();

            return view('simulation_model.view', compact('show_results', 'heightList', 'policyList', 'windDirections'));
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 公開用のシミュレーション結果閲覧(可視化種別により表示)
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function changeShowModePublish(Request $request, string $id)
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
            if (!$simulationModel) {
                throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーション結果閲覧に失敗しました。「{$id}」のシミュレーションモデルが存在しません。");
            } else if (!$simulationModel->disclosure_flag) {
                // 閲覧用のシミュレーションモデルが公開されない場合はエラースルー
                throw new Exception("可視化種別や高さにより表示切替に失敗しました。{$id}」のシミュレーションモデルが一般公開されていません。");
            } else {
                // 可視化ファイル
                $visualization = SimulationModelService::getVisualization($id, $visualizationType, $heightId);
                if ($visualization) {
                    return view('simulation_model.view_publish', compact(
                        'simulationModel',
                        'heightList',
                        'visualizationType',
                        'heightId',
                        'visualization',
                        'mapCurrentHeading',
                        'mapCurrentPitch',
                        'mapCurrentRoll',
                        'mapCurrentPositionX',
                        'mapCurrentPositionY',
                        'mapCurrentPositionZ'
                    ));
                } else {
                    throw new Exception("可視化種別や高さにより表示切替に失敗しました。シミュレーションモデルID: {$id}, 可視化種別: {$visualizationType}, 高さ: {$heightId} の可視化ファイルが見つかりませんでした。");
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error_publish', compact('e'));
        }
    }

    /**
     * シミュレーション結果（GeoJSON）ファイルをダウンロード
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     * @param int $map_id  特定地図
     *
     * @return
     */
    public function download(Request $request, string $id, int $map_id)
    {
        try {

            // 選択したラジオボタン
            $visualizationType = $request->query->get('visualization_type_' . $map_id);
            // 選択した高さ
            $heightId = $request->query->get('height_' . $map_id);

            // 固定値利用可否モード
            $legendType = ($request->query->get('ckb_value_fixed_' . $map_id) != null) ? $request->query->get('ckb_value_fixed_' . $map_id) : Constants::LEGENF_TYPE_FLUCTUATION;

            // 可視化ファイル
            $visualization = SimulationModelService::getVisualization($id, $visualizationType, $heightId, $legendType);

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

    /**
     * 公開用の画面でシミュレーション結果（GeoJSON）ファイルをダウンロード
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function downloadPublish(Request $request, string $id)
    {
        try {

            // 選択したラジオボタン
            $visualizationType = $request->query->get('visualization_type');
            // 選択した高さ
            $heightId = $request->query->get('height');

            $simulationModel = SimulationModelService::getSimulationModelById($id);
            if (!$simulationModel) {
                throw new Exception("シミュレーション結果閲覧に失敗しました。「{$id}」のシミュレーションモデルが存在しません。");
            } else if (!$simulationModel->disclosure_flag) {
                // 閲覧用のシミュレーションモデルが公開されない場合はエラースルー
                throw new Exception("シミュレーション結果閲覧に失敗しました。{$id}」のシミュレーションモデルが一般公開されていません。");
            } else {

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
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error_publish', compact('e'));
        }
    }

    /**
     * 実施施策一覧に実施施策を新規追加
     * @param Request $request リクエスト
     *
     * @return
     */
    public function createSmPolicy(Request $request)
    {
        try {

            // 編集対象のシミュレーションモデルID
            $simulationModelId = $request->simulation_model_id;
            // 追加しようとする対象
            $stlTypeId = $request->stl_type_id;
            // 追加しようとする施設
            $policyId = $request->policy_id;

            $smPoliciesInfos = [];

            // 施設または対象が未選択の場合、エラーを表示
            if (!$policyId || !$stlTypeId) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                return response()->json($errorMessage);
            } else {

                if ($simulationModelId) {

                    // シミュレーションモデルのレコードを取得
                    $simulationModel = SimulationModelService::getSimulationModelById($simulationModelId);

                    if (!$simulationModel) {
                        throw new Exception("実施施策一覧に新規追加に失敗しました。シミュレーションモデルID 「{$simulationModelId}」のレコードが存在していません。");
                    }

                    // STLファイル一覧のhtml生成ようのデータ
                    $paritalViewSmPolicyData = null;

                    // シミュレーション結果閲覧画面でシミュレーション再作成を行った場合のみ、特定地図(map_id = 0 or 1..)の指定が必要。
                    $mapId = $request->map_id;
                    if (!is_null($mapId)) {
                        // シミュレーションモデルにより、セッションキーが異なる。
                        $smPolicySessionKey = Constants::RECREATE_SM_POLICY_SESSION_KEY . $simulationModel->simulation_model_id;
                        // 最新の実施施策一覧
                        $paritalViewSmPolicyData = [
                            'smPolicies' => SimulationModelService::addNewSmPolicy($request, $simulationModel, $stlTypeId, $policyId, $smPolicySessionKey),
                            'simulationModelId' => $simulationModelId,
                            'mapId' => $mapId
                        ];
                    } else {
                        // シミュレーションモデルにより、セッションキーが異なる。
                        $smPolicySessionKey = Constants::SM_POLICY_SESSION_KEY . $simulationModel->simulation_model_id;
                        // 最新の実施施策一覧
                        $paritalViewSmPolicyData = [
                            'smPolicies' => SimulationModelService::addNewSmPolicy($request, $simulationModel, $stlTypeId, $policyId, $smPolicySessionKey),
                            'simulationModelId' => $simulationModelId,
                        ];
                    }

                    // STLファイル一覧のhtmlデータ
                    $paritalViewSmPolicy = View::make('simulation_model.partial_sm_policy.partial_sm_policy_list', $paritalViewSmPolicyData)->render();

                    // レスポンス情報
                    $smPoliciesInfos = ['paritalViewSmPolicy' => $paritalViewSmPolicy];
                    return response()->json($smPoliciesInfos);
                } else {
                    throw new Exception("実施施策一覧に新規追加に失敗しました。シミュレーションモデルID: {$simulationModelId}が不正です。");
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }
    /**
     * 実施施策一覧より実施施策を削除
     * @param Request $request リクエスト
     *
     * @return
     */
    public function deleteSmPolicy(Request $request)
    {
        try {

            // 編集対象のシミュレーションモデルID
            $simulationModelId = $request->simulation_model_id;
            // 追加しようとする対象
            $stlTypeId = $request->stl_type_id;
            // 追加しようとする施設
            $policyId = $request->policy_id;

            $smPoliciesInfos = [];

            // 施設または対象が未選択の場合、エラーを表示
            if (!$policyId || !$stlTypeId) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];
                return response()->json($errorMessage);
            } else {

                if ($simulationModelId) {

                    // シミュレーションモデルのレコードを取得
                    $simulationModel = SimulationModelService::getSimulationModelById($simulationModelId);

                    if (!$simulationModel) {
                        throw new Exception("実施施策一覧より行削除に失敗しました。シミュレーションモデルID 「{$simulationModelId}」のレコードが存在していません。");
                    }

                    // STLファイル一覧のhtml生成ようのデータ
                    $paritalViewSmPolicyData = null;

                    // シミュレーション結果閲覧画面でシミュレーション再作成を行った場合のみ、特定地図(map_id = 0 or 1..)の指定が必要。
                    $mapId = $request->map_id;
                    if (!is_null($mapId)) {
                        // シミュレーションモデルにより、セッションキーが異なる。
                        $smPolicySessionKey = Constants::RECREATE_SM_POLICY_SESSION_KEY . $simulationModel->simulation_model_id;
                        // 最新の実施施策一覧
                        $paritalViewSmPolicyData = [
                            'smPolicies' => SimulationModelService::deleteSmPolicy($request, $simulationModel, $stlTypeId, $policyId, $smPolicySessionKey),
                            'simulationModelId' => $simulationModelId,
                            'mapId' => $mapId
                        ];
                    } else {
                        // シミュレーションモデルにより、セッションキーが異なる。
                        $smPolicySessionKey = Constants::SM_POLICY_SESSION_KEY . $simulationModel->simulation_model_id;
                        // 最新の実施施策一覧
                        $paritalViewSmPolicyData = [
                            'smPolicies' => SimulationModelService::deleteSmPolicy($request, $simulationModel, $stlTypeId, $policyId, $smPolicySessionKey),
                            'simulationModelId' => $simulationModelId,
                        ];
                    }

                    // STLファイル一覧のhtmlデータ
                    $paritalViewSmPolicy = View::make('simulation_model.partial_sm_policy.partial_sm_policy_list', $paritalViewSmPolicyData)->render();

                    // レスポンス情報
                    $smPoliciesInfos = ['paritalViewSmPolicy' => $paritalViewSmPolicy];
                    return response()->json($smPoliciesInfos);
                } else {
                    throw new Exception("実施施策一覧より行削除に失敗しました。シミュレーションモデルID: {$simulationModelId}が不正です。");
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }

    /**
     * 凡例種別値変更
     * @param Request $request リクエスト
     * @param string $id シミュレーションモデルID
     *
     * @return
     */
    public function changeLegendType(Request $request, string $id)
    {
        try {

            $result = [];

            if ($id) {

                // 表示モード(風況、中空温度、暑さ指数)
                $visualizationType = $request->visualizationType;
                // 高さ
                $heightId = $request->heightId;
                // 凡例種別値
                $legendType = $request->legendType;

                // 可視化ファイルを取得
                $visualization = SimulationModelService::getVisualization($id, $visualizationType, $heightId, $legendType);
                if (!$visualization) {
                    // レコードが存在しない場合
                    $errorMessage = ["type" => "E", "code" => "E36", "msg" => Message::$E36];
                    $result = [
                        "error" => $errorMessage
                    ];
                } else {
                    $result = [
                        "error" => "",
                        "visualization" => $visualization
                    ];
                }
            } else {
                throw new Exception("凡例種別値変更に失敗しました。シミュレーションモデルID: {$id}が不正です。");
            }

            return response()->json($result);
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }

    /**
     * 再作成のシミュレーションモデルを新規登録
     * @param Request $request リクエスト
     *
     * @return
     */
    public function recreateSimulationModel(Request $request)
    {
        try {

            $result = [];

            if ($request && !is_null($request->map_id)) {
                // 編集操作ができるか確認 E9, E19, E20
                $errorMessage = SimulationModelService::recreateIsOk($request);

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    $result = [
                        "error" => $errorMessage
                    ];
                } else {

                    DB::beginTransaction();

                    // 元のシミュレーションモデルID
                    $simulation_model_id_src = $request->simulation_model_id_src;

                    // シミュレーションモデルの再作成
                    $recreateResult = SimulationModelService::recreateSimulation($request, self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id, $simulation_model_id_src);
                    if ($recreateResult['result']) {
                        DB::commit();
                        foreach ($recreateResult['log_infos'] as $key => $log) {
                            LogUtil::i($log);
                        }

                        // シミュレーションモデルにより、セッションキーが異なる。
                        $smPolicySessionKey = Constants::RECREATE_SM_POLICY_SESSION_KEY . $simulation_model_id_src;

                        // 実施施策一覧に行を追加や削除の際に一時的に設定したセッションデータを削除
                        $request->session()->forget($smPolicySessionKey);

                        $result = [
                            "error" => "",
                            // 保存処理に成功したら、シミュレーションモデル一覧画面に遷移する。
                            "redirect" => route('simulation_model.index')
                        ];
                    } else {
                        throw new Exception("シミュレーションモデルの再作成に失敗しました。");
                    }
                }
            } else {
                throw new Exception("シミュレーションモデルの再作成に失敗しました。特定地図が不明");
            }
            // レスポンス
            return response()->json($result);
        } catch (Exception $e) {
            DB::rollBack();
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }
}
