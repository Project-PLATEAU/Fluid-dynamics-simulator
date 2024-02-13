<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

/**
 * ログのユーティリティクラス
 */
class LogUtil
{

    /**
     * ログ出力
     *
     * @param string $level ログレベル
     * @param string $args メッセージ。sprintfに引き渡す配列
     */
    public static function log($level, $args)
    {
        $message = '';

        if (count($args) > 0) {
            $message = call_user_func_array('sprintf', $args);
        }

        switch ($level) {
            case 'i':
                Log::info($message);
                break;
            case 'w':
                Log::warning($message);
                break;
            case 'e':
                Log::error($message);
                break;
        }
    }

    /**
     * traceレベルの ログ出力
     *
     * @param mixed $args ログメッセージをsprintf関数の引数と同じ形式で指定する
     */
    public static function t()
    {
        self::log('t', func_get_args());
    }

    /**
     * infoレベルの ログ出力
     *
     * @param mixed $args ログメッセージをsprintf関数の引数と同じ形式で指定する
     */
    public static function i()
    {
        self::log('i', func_get_args());
    }

    /**
     * warningレベルの ログ出力
     *
     * @param mixed $args ログメッセージをsprintf関数の引数と同じ形式で指定する
     */
    public static function w()
    {
        self::log('w', func_get_args());
    }

    /**
     * errorレベルの ログ出力
     *
     * @param mixed $args ログメッセージをsprintf関数の引数と同じ形式で指定する
     */
    public static function e()
    {
        self::log('e', func_get_args());
    }
}
