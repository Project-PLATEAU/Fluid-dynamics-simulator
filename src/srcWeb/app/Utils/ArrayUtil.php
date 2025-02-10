<?php

namespace App\Utils;

/**
 * 配列の処理
 */
class ArrayUtil
{
    /**
     * usortを利用して、指定したキーの昇順にソート
     *
     * @param array $array ソート対象配列
     * @param string $key 特定キーでソートする。
     *
     * @return
     */
    public static function sortArrayBykey($array, $key)
    {
        usort($array, function ($a, $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });
        return $array;
    }
}
