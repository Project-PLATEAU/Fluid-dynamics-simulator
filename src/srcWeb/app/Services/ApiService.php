<?php


namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

/**
 * APIサービス
 */
class ApiService extends BaseService
{

    // CZML変換API
    const CONVERT_TO_CZML_API = "http://api:8000/convert_to_czml";
    // 建物作成API
    const NEW_BUILDING_API = "http://api:8000/new_building";
    // 建物削除API
    const REMOVE_BUILDING_API = "http://api:8000/remove_building";

    // リクエストメソッド：POST
    const HTTP_POST = "POST";
    // リクエストメソッド：GET
    const HTTP_GET = "GET";

    // ステータスコード：正常
    const STATUS_CODE_201 = 201;
    // リクエストパラメータが不正
    const STATUS_CODE_400 = 400;
    // 該当するOBJ/STLファイルが編集中である
    const STATUS_CODE_409 = 409;
    // サーバー内でエラー発生
    const STATUS_CODE_500 = 500;

    /**
     * APIを呼び出して結果を取得する共通メソッド
     *
     * @param string $url APIのパス
     * @param array $params リクエストのパラーメター
     * @param string $method リクエストメソッド(POST or GET)
     * @return array レスポンスデータ(ステータスコードとメッセージ)
     *   例：[
     *         "status_code" => 201,
     *         "msg" => ""
     *      ]
     */
    public static function fetchData($url, $params = [], $method = self::HTTP_POST)
    {

        $result = [];
        switch ($method) {
            // POSTリクエスト
            case self::HTTP_POST:
                $response = Http::post($url, $params);
                break;
            // GETリクエスト
            case self::HTTP_GET:
                $response = Http::get($url, $params);
                break;
            // デフォルト値: POSTリクエスト
            default:
                $response = Http::post($url, $params);
                break;
        }

        // ステータス
        $statusCode = $response->status();

        $result = [
            "status_code" => $response->status()
        ];

        // レスポンスデータ(メッセージなど)
        $responseData = $response->json();
        if ($statusCode == self::STATUS_CODE_400) {
            $result["detail"] = (isset($responseData) && isset($responseData["detail"]))? $responseData["detail"] : [];
        } else {
            $result["msg"] = (isset($responseData) && isset($responseData["msg"])) ? $responseData["msg"] : "";
        }
        return $result;
    }

    /**
     * レスポンスのステータスを取得する。
     *
     * @param array $responseData レスポンスしたデータ
     *  例：[
     *         "status_code" => 201,
     *         "msg" => ""
     *      ]
     *
     * @return integer apiのステータス
     */
    public static function getStatusCode($responseData)
    {
        return $responseData['status_code'];
    }

    /**
     * CZML変換APIを呼び出す
     *
     * @param array $params リクエストのパラーメター
     *
     * @return string apiのステータス
     */
    public static function callConvertToCzmlAPI($params = [])
    {
        $responseData = self::fetchData(self::CONVERT_TO_CZML_API, $params);
        $statusCode = self::getStatusCode($responseData);
        return $statusCode;
    }

    /**
     * 建物作成APIを呼び出す。
     *
     * @param array $params リクエストのパラーメター
     *
     * @return string apiのステータス
     */
    public static function callNewBuildingAPI($params = [])
    {
        $responseData = self::fetchData(self::NEW_BUILDING_API, $params);
        $statusCode = self::getStatusCode($responseData);
        return $statusCode;
    }

    /**
     * 建物削除APIを呼び出す。
     *
     * @param array $params リクエストのパラーメター
     *
     * @return string apiのステータス
     */
    public static function callRemoveBuildingAPI($params = [])
    {
        $responseData = self::fetchData(self::REMOVE_BUILDING_API, $params);
        $statusCode = self::getStatusCode($responseData);
        return $statusCode;
    }

    /**
     * API呼び出しに異常があったかチェックする。
     *
     * @param $statusCode ステータスコード
     *
     * @return bool
     *  API呼び出しに異常があった場合、trueを返す。
     *  API呼び出しに異常がなかった場合、falseを返す。
     */
    public static function isError($statusCode)
    {
        if ($statusCode == self::STATUS_CODE_400 || $statusCode == self::STATUS_CODE_500) {
            return true;
        }
        return false;
    }

    /**
     * API呼び出しに競合があったかチェックする。
     *
     * @param $statusCode ステータスコード
     *
     * @return bool
     *  API呼び出しに競合があった場合、trueを返す。
     *  API呼び出しに競合がなかった場合、trueを返す。
     */
    public static function isConflict($statusCode)
    {
        if ($statusCode == self::STATUS_CODE_409) {
            return true;
        }
        return false;
    }
}
