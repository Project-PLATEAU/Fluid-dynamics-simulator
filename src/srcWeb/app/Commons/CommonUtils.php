<?php

namespace App\Commons;

use App\Utils\DatetimeUtil;
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
        // $_3dtiles_url = "https://raw.githubusercontent.com/Project-PLATEAU/plateau-streaming-tutorial/main/3dtiles_url.json";
        $_3dtiles_url = "https://api.plateauview.mlit.go.jp/datacatalog/plateau-datasets";
        $response = Http::get($_3dtiles_url);

        // ============レスポンス例========================================================================
        // API: https://api.plateauview.mlit.go.jp/datacatalog/plateau-datasets
        //
        // {
        //     "datasets": [
        //         {
        //         "id": "01101_bldg_lod1",
        //         "name": "建築物モデル（中央区）",
        //         "pref": "北海道",
        //         "pref_code": "01",
        //         "city": "札幌市",
        //         "city_code": "01100",
        //         "ward": "中央区",
        //         "ward_code": "01101",
        //         "type": "建築物モデル",
        //         "type_en": "bldg",
        //         "url": "https://assets.cms.plateau.reearth.io/assets/b8/314602-4b39-4d5f-be2d-a0b17a3e3c21/01100_sapporo-shi_city_2020_citygml_6_op_bldg_3dtiles_01101_chuo-ku_lod1/tileset.json",
        //         "layers": null,
        //         "year": 2020,
        //         "registration_year": 2023,
        //         "spec": "3.3",
        //         "format": "3D Tiles",
        //         "lod": "1",
        //         "texture": true
        //         },
        //         {
        //         ...
        //         }
        //     ]
        // }
        // ============レスポンス例 //======================================================================

        // HTTPリクエストが成功した場合（ステータスコードが2xx)
        if ($response->successful()) {

            // JSONレスポンスをパース
            $_3dtilesArr = $response->json();

            // 条件に基づいてデータをフィルタリングする
            //  type_en: bldg
            //  lot:1
            // $filtered3dtiles = array_filter($_3dtilesArr, function ($_3dtiles) {
            //     if (array_key_exists('type_en', $_3dtiles) && array_key_exists('lod', $_3dtiles)) {
            //         return ($_3dtiles['type_en'] == "bldg" && $_3dtiles['lod'] == "1");
            //     }
            // });

            $datasets = $_3dtilesArr['datasets'];
            $filtered3dtiles = array_filter($datasets, function ($_3dtiles) {
                if (array_key_exists('type_en', $_3dtiles) && array_key_exists('lod', $_3dtiles)) {
                    return ($_3dtiles['type_en'] == "bldg" && $_3dtiles['lod'] == "1");
                }
            });
            return $filtered3dtiles;
        } else {
            return [];
        }
    }

    /**
     * gitへ最終コミットがされた日時を取得
     * @return
     */
    public static function getLastGitCommitDate()
    {
        $commitHash = trim(shell_exec('git rev-parse HEAD'));
        $commitDate = shell_exec("git log -1 --pretty=format:'%ci' $commitHash");
        return DatetimeUtil::changeFormat($commitDate, 'Y.m.d');
    }
}
