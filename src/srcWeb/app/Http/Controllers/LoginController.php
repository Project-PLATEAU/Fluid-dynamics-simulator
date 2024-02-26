<?php

namespace App\Http\Controllers;

use App\Commons\CommonUtils;
use App\Commons\Constants;
use App\Commons\Message;
use App\Models\Db\UserAccount;
use App\Services\LoginService;
use App\Utils\LogUtil;
use Exception;
use Illuminate\Http\Request;

/**
 * ログイン画面用のコントロール
 */
class LoginController extends BaseController
{
    /**
     * 初期表示
     */
    public function index()
    {
        // 他画面からのリダイレクトで渡されたデータを受け取る。
        $message = session('message');
        $userAccount = new UserAccount();
        // gitへ最終コミットがされた日時をバージョンとする。
        $version = CommonUtils::getLastGitCommitDate();
        return view('login.index', compact('userAccount', 'message', 'version'));
    }


    /**
     *
     * ログイン
     *
     * @param Request $request ログインリクエスト
     *
     * @return
     */
    public function login(Request $request)
    {
        try {

            $userInfo = LoginService::auth($request->user_id, $request->password);

            // ログイン認証に成功した場合
            if ($userInfo) {
                // クッキー設定
                self::setCookie(Constants::LOGIN_COOKIE_NAME, $userInfo->toArray());

                // 都市モデル一覧へ遷移する。
                return redirect()->route('city_model.index');
            }

            // ログイン認証に失敗した場合
            $errorMessage = ["type" => "E", "code" => "E1", "msg" => Message::$E1];
            LogUtil::w($errorMessage["msg"]);
            return redirect()->route('login.index')->with(['message' => $errorMessage]);

        } catch (Exception $e) {
            $error = $e->getMessage();
            LogUtil::e($error);
            return view('layouts.error', compact('e'));
        }
    }

    /**
     * ログアウト
     *
     * @return
     */
    public function logout()
    {
        self::deleteCookie(Constants::LOGIN_COOKIE_NAME);
        // ログイン画面に遷移
        return redirect()->route('login.index');
    }
}
