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

    public function run(): void
    {
        Customer::factory(10)->create();

        IspPlan::insert([
            [
                'name' => 'Home Starter',
                'price' => 25000,
                'upload_speed' => 10,
                'download_speed' => 20,
                'description' => 'Basic internet for browsing, messaging and social media. Best for 1–2 users.',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Home Basic',
                'price' => 35000,
                'upload_speed' => 15,
                'download_speed' => 30,
                'description' => 'Stable internet for streaming, online classes and daily home usage.',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Home Plus',
                'price' => 55000,
                'upload_speed' => 25,
                'download_speed' => 50,
                'description' => 'Fast HD streaming, video calls and light gaming for small families.',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fiber Family',
                'price' => 75000,
                'upload_speed' => 35,
                'download_speed' => 70,
                'description' => 'High-speed fiber for multiple devices, streaming and online learning.',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium Ultra',
                'price' => 95000,
                'upload_speed' => 50,
                'download_speed' => 100,
                'description' => 'Ultra-fast internet for 4K streaming, gaming and smart home use.',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business Pro',
                'price' => 120000,
                'upload_speed' => 75,
                'download_speed' => 150,
                'description' => 'Enterprise-grade stable connection for offices and heavy usage.',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        ServiceArea::insert([
            [
                'region' => 'Yangon',
                'city' => 'Yangon',
                'township' => 'Hlaing',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Yangon',
                'city' => 'Yangon',
                'township' => 'Sanchaung',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Yangon',
                'city' => 'Yangon',
                'township' => 'Mayangone',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Yangon',
                'city' => 'Yangon',
                'township' => 'Bahan',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Mandalay',
                'city' => 'Mandalay',
                'township' => 'Chanayethazan',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Mandalay',
                'city' => 'Mandalay',
                'township' => 'Aungmyethazan',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Mandalay',
                'city' => 'Mandalay',
                'township' => 'Pyigyitagon',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Naypyidaw',
                'city' => 'Naypyidaw',
                'township' => 'Zabuthiri',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Naypyidaw',
                'city' => 'Naypyidaw',
                'township' => 'Pyinmana',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'region' => 'Shan',
                'city' => 'Taunggyi',
                'township' => 'Taunggyi',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        Cpe::factory(100)->create();
    }
}
