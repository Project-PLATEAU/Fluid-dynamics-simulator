<?php

namespace App\Http\Controllers;
use App\Services\CityModelReferenceAuthorityService;
use App\Services\SimulationModelReferenceAuthorityService;
use App\Services\CityModelService;
use App\Services\SimulationModelService;
use App\Commons\Constants;
use App\Commons\Message;
use App\Models\Db\UserAccount;
use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;

/**
 * モデル共有のコントロール
 */
class ShareModelController extends BaseController
{
    /**
     * モデル共有画面の初期表示
     */
    public function index()
    {
        // 他画面からのリダイレクトで渡されたデータを受け取る。
        try {
            $shareMode = session('share_mode') ? session('share_mode') : null;
            $model = session('model') ? session('model') : null;
            $userId = session('user_id') ? session('user_id') : null;
            $message = session('message') ? session('message') : null;

            if (!$shareMode || !$model) {
                throw new Exception ("モデル共有画面遷移に失敗しました。3D都市モデル一覧画面かシミュレーションモデル一覧よりやり直してください。");
            } else {
                if ($shareMode == Constants::SHARE_MODE_CITY_MODEL) {
                    $userList = $model->city_model_reference_authoritys()->get();
                    $modelId = $model->city_model_id;
                } else if ($shareMode == Constants::SHARE_MODE_SIMULATION_MODEL) {
                    $userList = $model->simulation_model_reference_authorities()->get();
                    $modelId = $model->simulation_model_id;
                }
                return view('share.index', compact('shareMode', 'message', 'userList', 'model', 'modelId', 'userId'));
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * 共有ユーザの追加を行う
     * @param Request $request リクエスト
     * @param string $shareMode シェアモード
     * @param string $id モデルID
     *
     * @return
     */
    public function store(Request $request, string $shareMode, string $modelId)
    {
        try {
            $isStoreFlg = $request->query->get('store_flg');

            $modelShare = null;

            if ($isStoreFlg) {
                $userId = $request->query->get('user_id');
                if ($shareMode == Constants::SHARE_MODE_CITY_MODEL) {
                    // 共有するユーザを都市モデル参照権限テーブルに追加
                    if (CityModelReferenceAuthorityService::addNewCityModelReferenceAuthority($userId, $modelId)) {
                        $modelShare = CityModelService::getCityModelById($modelId);
                    } else {
                        throw new Exception("都市モデルの共有ユーザの追加に失敗しました。都市モデルID: {$modelId}, ユーザID: {$userId}");
                    }
                } else {
                    // 共有するユーザをシミュレーションモデル参照権限に追加
                    if (SimulationModelReferenceAuthorityService::addNewSimulationModelReferenceAuthority($userId, $modelId)) {
                        $modelShare = SimulationModelService::getSimulationModelById($modelId);
                    } else {
                        throw new Exception("シミュレーションモデルの共有ユーザの追加に失敗しました。シミュレーションモデルID: {$modelId}, ユーザID: {$userId}");
                    }
                }

                return redirect()->route('share.index')->with(['share_mode' => $shareMode, 'model' => $modelShare,  'user_id' => $userId]);
            } else {

                $errorMessage = [];

                $modelShare = null;
                $userList = null;
                //共有先ユーザID
                $userId = $request->identification_name;

                // 共有済ユーザ一覧に掲示されているユーザID保存不可
                if ($shareMode == Constants::SHARE_MODE_CITY_MODEL) {
                    $modelShare = CityModelService::getCityModelById($modelId);
                    $userList = $modelShare->city_model_reference_authoritys()->get();
                } else {
                    $modelShare = SimulationModelService::getSimulationModelById($modelId);
                    $userList = $modelShare->simulation_model_reference_authorities()->get();
                }
                foreach ($userList as $user) {
                    if ($user->user_id == $userId) {
                        $errorMessage = ["type" => "E", "code" => "E6", "msg" => Message::$E6];
                    }
                }

                // ユーザアカウントテーブルにないユーザID保存不可
                if (!$errorMessage) {
                    $userAccount = UserAccount::find($userId);
                    if (!$userAccount) {
                        $errorMessage = ["type" => "E", "code" => "E7", "msg" => Message::$E7];
                    }
                }

                // 画面遷移
                if ($errorMessage) {
                    LogUtil::w($errorMessage["msg"]);
                    return redirect()->route('share.index')->with(['message' => $errorMessage, 'share_mode' => $shareMode, 'model' => $modelShare]);
                } else {
                    $warningMessage = ["type" => "W", "code" => "W2", "msg" => sprintf(Message::$W2, $userId)];
                    return redirect()->route('share.index')->with(['message' => $warningMessage, 'share_mode' => $shareMode, 'model' => $modelShare,  'user_id' => $userId]);
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
     * 共有済ユーザの解除を行う。
     *
     * @param Request $request 削除リクエスト
     * @param string $shareMode シェアモード
     * @param string $id モデルID
     *
     * @return
     */
    public function destroy(Request $request, string $shareMode, string $modelId)
    {
        try {

            $errorMessage = [];

            // 登録ユーザ
            $userIdsString = $request->query->get('user_ids');
            // ハイフン区切りでユーザを配列にする
            $userIdsArray = explode('-', $userIdsString);

            $modelShare = null;

            // 削除操作ができるか確認
            if (!$userIdsString) {
                $errorMessage = ["type" => "E", "code" => "E2", "msg" => Message::$E2];

            } else {
                if ($shareMode == Constants::SHARE_MODE_CITY_MODEL) {
                    // 解除するユーザを都市モデル参照権限テーブルから削除
                    if (!CityModelReferenceAuthorityService::deleteCityModelReferenceAuthority($userIdsArray, $modelId)) {
                        throw new Exception("都市モデルの共有ユーザの削除に失敗しました。都市モデルID: {$modelId}, ユーザID: " .implode(', ', $userIdsArray));
                    }
                } else {
                    // 解除するユーザをシミュレーションモデル参照権限から削除
                    if (!SimulationModelReferenceAuthorityService::deleteSimulationModelReferenceAuthority($userIdsArray, $modelId)) {
                        throw new Exception("シミュレーションモデルの共有ユーザの削除に失敗しました。シミュレーションモデルID: {$modelId}, ユーザID: " .implode(', ', $userIdsArray));
                    }
                }
            }

            if ($shareMode == Constants::SHARE_MODE_CITY_MODEL) {
                $modelShare = CityModelService::getCityModelById($modelId);
            } else {
                $modelShare = SimulationModelService::getSimulationModelById($modelId);
            }

            // 画面遷移
            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                return redirect()->route('share.index')->with(['message' => $errorMessage, 'share_mode' => $shareMode, 'model' => $modelShare]);
            } else {
                return redirect()->route('share.index')->with(['share_mode' => $shareMode, 'model' => $modelShare]);
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }
}
