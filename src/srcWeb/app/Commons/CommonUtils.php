<?php

namespace App\Commons;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * 共通処理クラス
 */
class CommonUtils
{
    /**
     * 配信している3D都市モデルの3D TilesデータのURL一覧(github)より、特定3D Tilesデータを取得する。
     * @return array 特定都市モデル
     */
    public static function filter3Dtiles()
    {
        // 配信している3D都市モデルの3D TilesデータのURL一覧(github)
        // $_3dtiles_url = "https://github.com/Project-PLATEAU/plateau-streaming-tutorial/blob/main/3dtiles_url.json";
        $_3dtiles_url = "https://raw.githubusercontent.com/Project-PLATEAU/plateau-streaming-tutorial/main/3dtiles_url.json";
        $response = Http::get($_3dtiles_url);

        // HTTPリクエストが成功した場合（ステータスコードが2xx
        if ($response->successful()) {

            // JSONレスポンスをパース
            $_3dtilesArr = $response->json();

            // 条件に基づいてデータをフィルタリングする
            //  type_en: bldg
            //  lot:1
            $filtered3dtiles = array_filter($_3dtilesArr, function ($_3dtiles) {
                if (array_key_exists('type_en', $_3dtiles) && array_key_exists('lod', $_3dtiles)) {
                    return ($_3dtiles['type_en'] == "bldg" && $_3dtiles['lod'] == "1");
                }
            });
            return $filtered3dtiles;
        } else {
            return [];
        }
    }
}
