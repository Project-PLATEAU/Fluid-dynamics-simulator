<?php

namespace App\Http\Controllers;

use App\Commons\Constants;
use Illuminate\Support\Facades\Cookie;
use stdClass;

/**
 * 共通コントロール
 */
class BaseController extends Controller
{
    /**
     * クッキー設定
     *
     * @param array $value クッキーバリュー
     * @return void
     */
    public static function setCookie($name, $value)
    {
        // 有効期限設定
        $cookieExp = time() + Constants::LOGIN_COOKIE_EXPIRATION;

        // クッキー設定
        Cookie::queue($name, json_encode($value), $cookieExp);
    }

    /**
     * クッキー取得
     *
     * @param string $name クッキー名
     * @return stdClass クッキー値
     */
    public static function getCookie($name)
    {
        $cookieValue = json_decode(Cookie::get($name));
        return $cookieValue;
    }

    /**
     * クッキー取得
     *
     * @param string $name クッキー名
     * @return void
     */
    public static function deleteCookie($name)
    {
        Cookie::queue(Cookie::forget($name));
    }


    /**
     * ログイン中のユーザなのか確認
     *
     * @param mixed $user_id ログインユーザID
     *
     * @return bool
     *  true: ログイン中のユーザであること
     *  false:ログイン中のユーザでないこと
     */
    public static function isLoginUser($user_id)
    {
        return (self::getCookie(Constants::LOGIN_COOKIE_NAME)->user_id == $user_id);
    }
}
