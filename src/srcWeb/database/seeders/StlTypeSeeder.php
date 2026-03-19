<?php

namespace Database\Seeders;

use App\Models\Db\StlType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StlTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // STLファイル種別の初期データを作成
        $stlTypeData = [
            [
                'stl_type_id'           =>  1,
                'stl_type_name'         =>  '建物(事務所)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  72
            ],
            [
                'stl_type_id'           =>  2,
                'stl_type_name'         =>  '建物(商業施設)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  323
            ],
            [
                'stl_type_id'           =>  3,
                'stl_type_name'         =>  '建物(宿泊施設)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  114
            ],
            [
                'stl_type_id'           =>  4,
                'stl_type_name'         =>  '建物(住宅)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  72
            ],
            [
                'stl_type_id'           =>  5,
                'stl_type_name'         =>  '建物(教育施設)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  242
            ],
            [
                'stl_type_id'           =>  6,
                'stl_type_name'         =>  '建物(その他)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  72
            ],
            [
                'stl_type_id'           =>  7,
                'stl_type_name'         =>  '建物(施策対象1)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  72
            ],
            [
                'stl_type_id'           =>  8,
                'stl_type_name'         =>  '建物(施策対象2)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  72
            ],
            [
                'stl_type_id'           =>  9,
                'stl_type_name'         =>  '建物(施策対象3)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  72
            ],
            [
                'stl_type_id'           =>  10,
                'stl_type_name'         =>  '建物(施策対象4)',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  72
            ],
            [
                'stl_type_id'           =>  11,
                'stl_type_name'         =>  '地表面(公園)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.5,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  12,
                'stl_type_name'         =>  '地表面(水面)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  13,
                'stl_type_name'         =>  '地表面(道路)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.9,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  14,
                'stl_type_name'         =>  '地表面(緑地)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.5,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  15,
                'stl_type_name'         =>  '地表面(その他)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  16,
                'stl_type_name'         =>  '地表面(施策対象1)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  17,
                'stl_type_name'         =>  '地表面(施策対象2)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  18,
                'stl_type_name'         =>  '地表面(施策対象3)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  19,
                'stl_type_name'         =>  '地表面(施策対象4)',
                'required_flag'         =>  false,
                'ground_flag'           =>  true,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  0.7,
                'heat_removal'          =>  0
            ],
            [
                'stl_type_id'           =>  20,
                'stl_type_name'         =>  '単独木',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  true,
                'plant_cover_flag'      =>  false,
                'solar_absorptivity'    =>  null,
                'heat_removal'          =>  null
            ],
            [
                'stl_type_id'           =>  21,
                'stl_type_name'         =>  '植被',
                'required_flag'         =>  false,
                'ground_flag'           =>  false,
                'tree_flag'             =>  false,
                'plant_cover_flag'      =>  true,
                'solar_absorptivity'    =>  null,
                'heat_removal'          =>  null
            ],
            // 新規追加のレコードはここから追加してください
        ];

        // トランザクションを使用してデータを挿入
        DB::transaction(function () use ($stlTypeData) {
            foreach ($stlTypeData as $stlType)
            {
                // 既存レコード更新 or 新規挿入
                StlType::updateOrCreate(
                    ['stl_type_id' => $stlType['stl_type_id']],
                    $stlType
                );
            }
        });
    }
}
