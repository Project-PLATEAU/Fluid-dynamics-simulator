<?php

namespace Database\Seeders;

use App\Models\Db\Height;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HeightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 相対高さの初期データを作成
        $heightData = [
            [
                'height_id' => 1,
                'height'    => 3
            ],
            [
                'height_id' => 2,
                'height'    => 5
            ],
            [
                'height_id' => 3,
                'height'    => 10
            ],
            [
                'height_id' => 4,
                'height'    => 20
            ],
            [
                'height_id' => 5,
                'height'    => 30
            ],
            // 新規追加のレコードはここから追加してください
        ];

        // トランザクションを使用してデータを挿入
        DB::transaction(function () use ($heightData) {
            foreach ($heightData as $height) {
                Height::updateOrCreate(
                    ['height_id' => $height['height_id']],
                    $height
                );
            }
        });
    }
}
