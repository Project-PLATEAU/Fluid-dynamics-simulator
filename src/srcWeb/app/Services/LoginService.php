<?php


namespace App\Services;

use App\Models\Db\UserAccount;


/**
 * ログインサービス
 */
class LoginService extends BaseService
{
    /**
     * ログイン認証
     * @param mixed $user_id ユーザID
     * @param mixed $password パスワード
     *
     * @return mixed
     *      true: UserAccount
     *      false: 認証に失敗
     */
    public static function auth($user_id, $password)
    {
        // ユーザアカウントのレコードと合致するか検証
        $userAccount = UserAccount::where(['user_id' => $user_id, 'password' => $password])->first();
        return $userAccount;
    }
}
