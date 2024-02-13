<?php

namespace Database\Seeders;

use App\Models\Db\Solver;
use App\Utils\DatetimeUtil;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SolverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Solver::create([
            'solver_name'               =>  '標準',
            'solver_compressed_file'    =>  'comperssed_solver/default/template.tar',
            'user_id'                   =>  'testuser',
            'upload_datetime'           =>  DatetimeUtil::getNOW(),
            'preset_flag'               =>  true,
            'disclosure_flag'           =>   true
        ]);
    }
}
