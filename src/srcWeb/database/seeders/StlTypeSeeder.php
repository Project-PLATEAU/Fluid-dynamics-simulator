<?php

namespace Database\Seeders;

use App\Models\Db\StlType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StlTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StlType::create([
            'stl_type_id'     =>  1,
            'stl_type_name'   =>  '建物(1)',
            'required_flag'   =>  true,
            'ground_flag'   =>  false,
        ]);
        StlType::create([
            'stl_type_id'     =>  2,
            'stl_type_name'   =>  '建物(2)',
            'required_flag'   =>  false,
            'ground_flag'   =>  false,
        ]);
        StlType::create([
            'stl_type_id'     =>  3,
            'stl_type_name'   =>  '建物(3)',
            'required_flag'   =>  false,
            'ground_flag'   =>  false,
        ]);
        StlType::create([
            'stl_type_id'     =>  4,
            'stl_type_name'   =>  '地面(1)',
            'required_flag'   =>  true,
            'ground_flag'   =>  true,
        ]);
        StlType::create([
            'stl_type_id'     =>  5,
            'stl_type_name'   =>  '地面(2)',
            'required_flag'   =>  false,
            'ground_flag'   =>  true,
        ]);
        StlType::create([
            'stl_type_id'     =>  6,
            'stl_type_name'   =>  '地面(3)',
            'required_flag'   =>  false,
            'ground_flag'   =>  true,
        ]);

    }
}
