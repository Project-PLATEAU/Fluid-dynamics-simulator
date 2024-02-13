<?php

namespace Database\Seeders;

use App\Models\Db\SimulationModel;
use App\Utils\DatetimeUtil;
use Illuminate\Database\Seeder;

class SimulationModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SimulationModel::create([
        //     'identification_name'               => 'test_kke',
        //     'city_model_id'	                    => 'f0070edd-91be-4462-90a4-4c5ca5091318',
        //     'region_id'		                    => '6a7d5869-2127-42f6-b714-510595b72457',
        //     'registered_user_id'	            => 'testuser',
        //     'last_update_datetime'	            => DatetimeUtil::getNOW(),
        //     'preset_flag'                       => true,
        //     'temperature'                       => 1,
        //     'wind_speed'                        => 1,
        //     'wind_direction'                    => 1,
        //     'solar_altitude_date'               => DatetimeUtil::getNOW(DatetimeUtil::DATE_FORMAT),
        //     'solar_altitude_time'               => 1,
        //     'south_latitude'                    => 1,
        //     'north_latitude'                    => 1,
        //     'west_longitude'                    => 1,
        //     'east_longitude'                    => 1,
        //     'ground_altitude'                   => 1,
        //     'sky_altitude'                      => 1,
        //     'solver_id'                         => 'c9e88ce3-bd52-4d1d-a300-3a440e347644',
        //     'mesh_level'                        => 0,
        //     'run_status'                        => null,
        //     'run_status_details'                => null,
        //     'cfd_error_log_file'                => null,
        //     'last_sim_start_datetime'           => DatetimeUtil::getNOW(),
        //     'last_sim_end_datetime'             => DatetimeUtil::getNOW()
        // ]);
    }
}
