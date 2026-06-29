<?php

namespace Database\Factories;

use App\Models\IspPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<IspPlan>
 */
class IspPlanFactory extends Factory {

  public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                      'Basic Home',
                      'Standard Plan',
                      'Premium Fiber',
                      'Ultra Speed',
                      'Business plan'
            ]) ,
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 25000, 100000),
            'status' => 1,
            'upload_speed' => fake()->randomElement([20,30,40,50,60]),
            'download_speed' =>  fake()->randomElement([20,30,40,50,60]),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

}
