<?php

namespace Tests;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
    }

    protected function seedRolesAndPermissions(): void
    {
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    }

    protected function createUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function admin(): User
    {
        return $this->createUserWithRole('admin');
    }

    protected function vendedor(): User
    {
        return $this->createUserWithRole('vendedor');
    }

    protected function comprador(): User
    {
        return $this->createUserWithRole('comprador');
    }

    protected function contador(): User
    {
        return $this->createUserWithRole('contador');
    }

    protected function gerente(): User
    {
        return $this->createUserWithRole('gerente');
    }

    protected function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin());
    }

    protected function actingAsRole(string $role): static
    {
        return $this->actingAs($this->createUserWithRole($role));
    }
}
