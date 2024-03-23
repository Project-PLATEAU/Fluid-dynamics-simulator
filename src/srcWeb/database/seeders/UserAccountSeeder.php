<?php

namespace Database\Seeders;

use App\Models\Db\UserAccount;
use App\Utils\DatetimeUtil;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserAccount::create([
            'user_id' => 'testuser',
            'password' => '',
            'display_name' => 'テストユーザ',
            'note' => '環境構築用DMLサンプルユーザ',
            'last_update_datetime' => DatetimeUtil::getNOW(),
        ]);
    }
}
