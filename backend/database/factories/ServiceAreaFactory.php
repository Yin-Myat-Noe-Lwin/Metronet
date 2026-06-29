<?php

namespace Database\Factories;

use App\Models\ServiceArea;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceArea>
 */
class ServiceAreaFactory extends Factory {

  public function definition(): array
    {
        return [
            'region' => fake()->randomElement([
              'Yangon'
            ]),
            'city' => fake()->randomElement([
              'Yangon'
            ]),
            'township' => fake()->randomElement([
              'Hlaing',
              'Dala',
              'Insein',
              'Botahtaung',
              'Kyauktada'
            ]),
            'status' => fake()->randomElement([0,1]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

}
