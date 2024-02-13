<?php

namespace Database\Seeders;

use App\Models\Db\Height;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HeightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Height::create([
            'height_id' => 1,
            'height'    => 1.5
        ]);
        Height::create([
            'height_id' => 2,
            'height'    => 5
        ]);
        Height::create([
            'height_id' => 3,
            'height'    => 10
        ]);
        Height::create([
            'height_id' => 4,
            'height'    => 30
        ]);
    }
}
