<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(UserAccountSeeder::class);
        $this->call(CityModelSeeder::class);
        $this->call(CityModelReferenceAuthoritySeeder::class);
        $this->call(CoordinateSeeder::class);
        $this->call(RegionSeeder::class);
        $this->call(SolverSeeder::class);
        $this->call(SimulationModelSeeder::class);
        $this->call(SimulationModelReferenceAuthoritySeeder::class);
        $this->call(HeightSeeder::class);
        $this->call(StlTypeSeeder::class);
        $this->call(StlTModelSeeder::class);
        $this->call(VisualizationSeeder::class);
        $this->call(SolarAbsorptivitySeeder::class);
        $this->call(PolicySeeder::class);
    }
}
