<?php

namespace Database\Factories;

use App\Models\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating Dashboard model test and seed data.
 *
 * This factory creates fake dashboard records and automatically
 * generates associated tenant and user records when they are not
 * explicitly provided. It is primarily used for database seeding,
 * automated testing, and development environments.
 *
 * @extends Factory<Dashboard>
 */
class DashboardFactory extends Factory
{
    /**
     * define the model's default state.
     *
     * generates a dashboard with a related tenant, creator user,
     * and a randomly generated dashboard name.
     */
    public function definition(): array
    {
        return [
            // automatically create and associate a tenant.
            'tenant_id' => Tenant::factory(),

            // automatically create and associate the user who created the dashboard.
            'created_by' => User::factory(),

            // generate a realistic dashboard name consisting of three words.
            'name' => fake()->words(3, true),
        ];
    }
}
