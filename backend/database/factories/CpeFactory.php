<?php

namespace Database\Factories;

use App\Models\Cpe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cpe>
 */
class CpeFactory extends Factory {

  public function definition(): array
    {
        return [
            'serial_number' => fake()->unique()->bothify('CPE-#########'),
            'mac_address' => fake()->unique()->macAddress(),
            'status' => fake()->randomElement([0,1,2,3,4]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

}
