<?php

namespace App\Http\Controllers;

use App\Commons\Message;
use App\Services\ApiService;
use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;

/**
 * 架空建物に対しての操作用のコントロール
 */
class BuildingController extends BaseController
{
    /**
     * 架空建物の新規作成を行う。
     * @param Request $request 架空建物の新規作成のリクエスト
     *
     * @return
     */
    public function create(Request $request)
    {
        try {

            $response = [
                "error" => []
            ];
            $rqParams = $request->post();

            $errorMessage = [];

            $apiStatusCode = ApiService::callNewBuildingAPI($rqParams);
            LogUtil::i("建物作成APIを呼び出しました。");

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
     * 架空建物の削除を行う。
     * @param Request $request 架空建物削除のリクエスト
     *
     * @return
     */
    public function destroy(Request $request)
    {
        try {

            $response = [
                "error" => []
            ];
            $rqParams = $request->post();

            $errorMessage = [];

            $apiStatusCode = ApiService::callRemoveBuildingAPI($rqParams);
            LogUtil::i("建物削除APIを呼び出しました。");

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
