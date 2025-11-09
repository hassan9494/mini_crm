<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Domains\Users\Seeders\RoleSeeder;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function createUserWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => $role], $attributes));
        $user->assignRole($role);

        return $user;
    }
}
