<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory {

  public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone_num' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'pending_email' => null,
            'status' => 1,
            'role' => fake()->randomElement([0,1]),
            'password' => Hash::make('password'),
            'verification_token' => null,
            'verification_token_expires_at' => null,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

}
