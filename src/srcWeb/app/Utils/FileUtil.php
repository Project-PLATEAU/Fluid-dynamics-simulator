<?php

namespace App\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ファイルのユーティリティクラス
 * ※view bladeで直接呼び出すため、「\Eloquent」クラスを継承する必要があります。
 */
class FileUtil extends \Eloquent
{

    /**
     * 都市モデル関連ファイルの格納先
     */
    const CITY_MODEL_FOLDER = "city_model";
    /**
     * ソルバ一式圧縮ファイルの格納先
     */
    const COMPRESSED_SOLVER_FOLDER = "compressed_solver";
    /**
     * シミュレーション実行に必要なファイルの格納先
     */
    const SIMULATION_INPUT_FOLDER = "simulation_input";
    /**
     * シミュレーション結果出力ファイルの格納先
     */
    const SIMULATION_OUTPUT_FOLDER = "simulation_output";
    /**
     * 可視化ファイルの格納先
     */
    const CONVERTED_OUTPUT_FOLDER = "converted_output";
    /**
     * 設定ファイルの格納先
     */
    const SETTING_FOLDER = "setting";
    /**
     * 熱流体解析エラーログファイル
     */
    const LOG_ZIP_FILE = "log.zip";
    /**
     * 風向表示設定用のJSONファイル
     */
    const WIND_DIRECTION_FILE = "wind_direction.json";

    /**
     * ファイル存在チェック
     * @param string $relativePath ファイル相対パス
     *
     * @return
     *  ファイル存在する場合：true
     *  ファイル存在しない場合：false
     */
    public static function isExists($relativePath)
    {
        return Storage::exists("public/" . $relativePath);
    }

    /**
     * ファイルダウンロード
     * @param string $relativePath ファイル相対パス
     * @param string $downloadFileName ダウンロードファイル名
     *
     * @return
     */
    public static function download($relativePath, $downloadFileName = null)
    {
        return Storage::download("public/" . $relativePath, $downloadFileName);
    }

    /**
     * ファイルアップロード
     * @param Request $file ファイルオブジェクト
     * @param string $relativePath ファイル相対パス
     * @param string $filename アップロードファイル名
     *
     * @return
     */
    public static function upload($file, $relativePath, $filename)
    {
        // アップロード処理
        $uploadPath = "/public/" . $relativePath;
        Storage::putFileAs($uploadPath, $file, $filename);
    }

    /**
     * ファイルを読み込む
     * @param string $relativePath ファイル相対パス
     *
     * @return string ファイル中身
     */
    public static function getStorageFile($relativePath)
    {
        $filePath = "/public/" . $relativePath;
        return Storage::get($filePath);
    }

    /**
     * publicフォルダからstorageフォルダのファイルを参照する
     * @param string $relativePath ファイル相対パス
     *
     * @return string ファイルパス
     */
    public static function referenceStorageFile($relativePath)
    {
        // publicフォルダからstorage/app/publicへのシンボリックリンクが作成したため、
        // publicフォルダからstorageフォルダのファイルを参照することができる。
        return asset('storage/' . $relativePath);
    }

    /**
     * 特定のフォルダをコピーする。
     * @param string $relativeSrc 相対パスのコピー元
     * @param string $relativeDes 相対パスのコピー先
     *
     * @return
     */
    public static function copyFolder($relativeSrc, $relativeDes)
    {
        $result = true;

        $source = "public/" . $relativeSrc;
        $destination = "public/" .$relativeDes;

        // ディレクトリ内のすべてのファイルとフォルダをコピー
        foreach (Storage::allFiles($source) as $file) {
            $relativePath = str_replace($source, '', $file);
            $newPath = $destination . $relativePath;
            $result = Storage::copy($file, $newPath);
            if(!$result) {
                return false;
            }
        }
        return $result;
    }

    /**
     * ファイルパスの末尾部分（ファイル名）を取得する。
     * @param string $path ファイルパス
     * @return string ファイル名
     * 　例：test.txt
     */
    public static function getFileName($path)
    {
        $fileName = "";
        if (self::isExists($path))
        {
            $fileName = basename($path);
        }
        return $fileName;
    }
}
