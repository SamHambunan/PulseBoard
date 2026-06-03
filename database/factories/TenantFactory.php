<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * factory for generating Tenant model test and seed data.
 *
 * this simply generates fake companies for testing purposes
 */

class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }
}
