<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


/**
 * factory for generating User model test and seed data.
 *
 * this factory creates fake user records and automatically generates
 * an associated tenant when one is not explicitly provided. It is
 * commonly used during database seeding, testing, and local development.
 *
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            // automatically create and associate a tenant if none is provided.
            'tenant_id' => Tenant::factory(),

            // generate a realistic user name.
            'name' => fake()->name(),

            // generate a unique and safe email address.
            'email' => fake()->unique()->safeEmail(),

            // use a cached hash of the default password ("password")
            // to avoid rehashing it for every generated user.
            'password' => static::$password ??= Hash::make('password'),

            // assign the default workspace role.
            'workspace_role' => 'viewer',

            // generate a random token used for "remember me" authentication.
            'remember_token' => Str::random(10),
        ];
    }
}
