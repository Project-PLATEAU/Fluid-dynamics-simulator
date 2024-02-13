<?php

namespace Database\Seeders;

use App\Models\Db\CityModel;
use App\Utils\DatetimeUtil;
use Illuminate\Database\Seeder;

class CityModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CityModel::create([
            'identification_name' => 'cm',
            'registered_user_id' => 'testuser',
            'last_update_datetime' => DatetimeUtil::getNOW(),
            'preset_flag' => true,
            'url' => 'url',
        ]);
    }
}
