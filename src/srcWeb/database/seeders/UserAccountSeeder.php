<?php

namespace Database\Seeders;

use App\Models\Db\UserAccount;
use App\Utils\DatetimeUtil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ユーザアカウントの初期データを作成
        $userAccountData = [
            [
                'user_id' => 'testuser',
                'password' => '',
                'display_name' => 'テストユーザ',
                'note' => '環境構築用DMLサンプルユーザ',
                'last_update_datetime' => DatetimeUtil::getNOW(),
            ],
            // 新規追加のレコードはここから追加してください
        ];

        // トランザクションを使用してデータを挿入
        DB::transaction(function () use ($userAccountData) {
           foreach ($userAccountData as $userAccount) {
                UserAccount::updateOrCreate(
                    ['user_id' => $userAccount['user_id']],
                    $userAccount
                );
            }
        });
    }
}
