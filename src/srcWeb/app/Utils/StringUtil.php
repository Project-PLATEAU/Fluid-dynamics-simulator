<?php

namespace App\Utils;

/**
* 文字列処理用のユーティリティクラス
*/
class StringUtil
{
    const SEPARATE_COMMA = ",";
    const SEPARATE_SLASH = "/";

    /**
     * 配列を文字列に変換
     * @param array $array 配列
     * @param  $separate 区切り
     *
     * @return string 変換済みの文字列
     */
    public static function arrayToString($array, $separate = self::SEPARATE_COMMA)
    {
        $str = implode($separate, $array);
        return $str;
    }

    /**
     * 文字列を配列に変換
     * @param mixed $str 文字列
     * @param  $separate 区切り
     *
     * @return array 配列
     */
    public static function stringToArray($str, $separate = self::SEPARATE_COMMA)
    {
        $initArr = explode($separate, $str);
        $arr = [];
        if (count($initArr) > 1) {
            foreach($initArr as $element) {
                // スペースの削除
                $arr[] = trim($element);
            }
        }
        return $arr;
    }
}
