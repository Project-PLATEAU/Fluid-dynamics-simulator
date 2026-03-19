<?php

namespace Database\Seeders;

use App\Models\Db\Information;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // インフォメーションの初期データを作成
        $informationData = [
            [
                'information_id'    => 1,
                'information'       => '高木または中木を選択すると以下のサイズの単独木を作成します。\n高木：高さ8m、樹冠直径4mの木。\n中木：高さ2m、樹冠直径1mの木。\n「手動入力」の場合、高さは{height_default}m以上、樹幹直径は{diameter_default}m以上で入力してください。'
            ],
            // 新規追加のレコードはここから追加してください
        ];

        // トランザクションを使用してデータを挿入
        DB::transaction(function () use ($informationData) {
            foreach ($informationData as $info) {
                Information::updateOrCreate(
                    ['information_id' => $info['information_id']],
                    $info
                );
            }
        });
    }
}
