<?php

namespace App\Utils;

use DateTime;

/**
 * 日時のユーティリティクラス
 * ※view bladeで直接呼び出すため、「\Eloquent」クラスを継承する必要があります。
 */
class DatetimeUtil extends \Eloquent
{
    // yyyy/MM/dd HH:mm 形式
    const DATE_TIME_FORMAT = "Y/m/d H:i";
    // yyyy/MM/dd 形式
    const DATE_FORMAT = "Y/m/d";

    /**
     * フォーマット変換
     * @param string $date 日時
     * @param string $format フォーマット
     */
    public static function changeFormat($dateTime, $format = self::DATE_TIME_FORMAT)
    {
        $d1 = new DateTime($dateTime);
        return $d1->format($format);
    }

    /**
     * 現在日時の取得
     * 現在日時を Y/m/d H:i形式で返します。
     *
     * @return string 現在日時(Y/m/d H:i形式)
     */
    public static function getNOW($format = self::DATE_TIME_FORMAT)
    {
        $now = new DateTime();
        return $now->format($format);
    }
}
