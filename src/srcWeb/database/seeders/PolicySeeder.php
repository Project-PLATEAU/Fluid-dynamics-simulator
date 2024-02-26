<?php

namespace Database\Seeders;

use App\Models\Db\Policy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Policy::create([
            'policy_id'             => 1,
            'policy_name'           => '打ち水',
            'solar_absorptivity'    => 0,
            'heat_removal'          => -100
        ]);
        Policy::create([
            'policy_id'             => 2,
            'policy_name'           => '屋上緑化',
            'solar_absorptivity'    => -0.2,
            'heat_removal'          => 0
        ]);
        Policy::create([
            'policy_id'             => 3,
            'policy_name'           => '壁面緑化',
            'solar_absorptivity'    => -0.2,
            'heat_removal'          => 0
        ]);
        Policy::create([
            'policy_id'             => 4,
            'policy_name'           => '敷地内植栽',
            'solar_absorptivity'    => -0.2,
            'heat_removal'          => 0
        ]);
    }
}
