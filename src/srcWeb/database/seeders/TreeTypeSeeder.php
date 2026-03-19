<?php

namespace Database\Seeders;

use App\Models\Db\TreeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // TreeTypeの初期データを作成
        $treeTypeData = [
            [
                'tree_type_id'      => 1,
                'tree_type_name'    => '高木',
                'canopy_diameter'   => 4,
                'height'            => 8
            ],
            [
                'tree_type_id'      => 2,
                'tree_type_name'    => '中木',
                'canopy_diameter'   => 1,
                'height'            => 2
            ],
            // 新規追加のレコードはここから追加してください
        ];

        // トランザクションを使用してデータを挿入
        DB::transaction(function () use ($treeTypeData) {
            foreach ($treeTypeData as $treeType) {
                TreeType::updateOrCreate(
                    ['tree_type_id' => $treeType['tree_type_id']],
                    $treeType
                );
            }
        });
    }
}
