<?php

namespace Database\Seeders;

use App\Models\Db\Policy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 熱対策施策の初期データを作成
        $policyData = [
            [
                'policy_id'             => 1,
                'policy_name'           => '打ち水',
                'solar_absorptivity'    => 0,
                'heat_removal'          => -230
            ],
            [
                'policy_id'             => 2,
                'policy_name'           => '屋上緑化',
                'solar_absorptivity'    => 0.3,
                'heat_removal'          => -100
            ],
            [
                'policy_id'             => 3,
                'policy_name'           => '壁面緑化',
                'solar_absorptivity'    => 0.3,
                'heat_removal'          => -100
            ],
            [
                'policy_id'             => 4,
                'policy_name'           => '敷地内植栽',
                'solar_absorptivity'    => 0.3,
                'heat_removal'          => -100
            ],
            // 新規追加のレコードはここから追加してください
        ];

        // トランザクションを使用してデータを挿入
        DB::transaction(function () use ($policyData) {
            foreach ($policyData as $policy) {
                Policy::updateOrCreate(
                    ['policy_id' => $policy['policy_id']],
                    $policy
                );
            }
        });
    }
}
