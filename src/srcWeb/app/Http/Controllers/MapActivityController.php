<?php

namespace App\Http\Controllers;

use App\Commons\Constants;
use App\Commons\Message;
use App\Services\ApiService;
use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;

/**
 * 3D地図に対しての操作（建物・樹木の追加・削除）用のコントロール
 */
class MapActivityController extends BaseController
{
    /**
     * モデル（建物・植被・単独木）新規作成を行う。
     * @param Request $request 作成のリクエスト
     * @param integer $model_type モデルタイプ(2:建物; 3:植被; 4:単独木)
     *
     * @return
     */
    public function create(Request $request, $model_type)
    {
        try {

            $response = [
                "error" => []
            ];
            $rqParams = $request->post();

            $errorMessage = [];

            $apiStatusCode = null;
            switch($model_type)
            {
                case Constants::ADDNEW_MODEL_BUILDING:
                    $apiStatusCode = ApiService::callNewBuildingAPI($rqParams);
                    LogUtil::i("建物作成APIを呼び出しました。");
                    break;
                case Constants::ADDNEW_MODEL_PLANT_COVER:
                    $apiStatusCode = ApiService::callNewPlantCoverAPI($rqParams);
                    LogUtil::i("植被作成APIを呼び出しました。");
                    break;
                case Constants::ADDNEW_MODEL_TREE:
                    $apiStatusCode = ApiService::callNewTreeAPI($rqParams);
                    LogUtil::i("単独木作成APIを呼び出しました。");
                    break;
                default:
                    break;
            }

            if (ApiService::isError($apiStatusCode)) {
                $errorMessage = ["type" => "E", "code" => "E34", "msg" => Message::$E34];
            } else if (ApiService::isConflict($apiStatusCode)) {
                $errorMessage = ["type" => "E", "code" => "E35", "msg" => Message::$E35];
            }

            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                $response["error"] = $errorMessage;
            }

            return response()->json($response);
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }

    /**
     * モデルの削除を行う。
     * @param Request $request モデル削除のリクエスト
     * @param integer $model_type モデルタイプ(1:モデル削除)
     *
     * @return
     */
    public function destroy(Request $request, $model_type)
    {
        try {

            $response = [
                "error" => []
            ];
            $rqParams = $request->post();

            $errorMessage = [];

            $apiStatusCode = null;
            switch($model_type)
            {
                case Constants::DELETE_MODEL:
                   $apiStatusCode = ApiService::callRemoveBuildingAPI($rqParams);
                    LogUtil::i("建物削除APIを呼び出しました。");
                    break;
                default:
                    break;
            }

            if (ApiService::isError($apiStatusCode)) {
                $errorMessage = ["type" => "E", "code" => "E34", "msg" => Message::$E34];
            } else if (ApiService::isConflict($apiStatusCode)) {
                $errorMessage = ["type" => "E", "code" => "E35", "msg" => Message::$E35];
            }

            if ($errorMessage) {
                LogUtil::w($errorMessage["msg"]);
                $response["error"] = $errorMessage;
            }

            return response()->json($response);
        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            header('HTTP/1.1 500 Internal Server');
            header('Content-Type: application/json; charset=UTF-8');
            die(json_encode(array('message' => 'error', 'code' => 500)));
        }
    }
}
