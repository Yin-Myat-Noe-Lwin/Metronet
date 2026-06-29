<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\IspPlan;
use App\Models\Cpe;
use App\Models\ServiceArea;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        Customer::factory(10)->create();

        IspPlan::factory(5)->create();

        ServiceArea::factory(5)->create();

        Cpe::factory(100)->create();
    }
}
