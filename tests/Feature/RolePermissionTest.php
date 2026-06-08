<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAsAdmin()
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_vendedor_can_access_dashboard(): void
    {
        $this->actingAsRole('vendedor')
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_user_without_role_is_forbidden_from_dashboard(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_vendedor_cannot_access_reports(): void
    {
        $this->actingAsRole('vendedor')
            ->get('/reports')
            ->assertForbidden();
    }

    public function test_contador_can_access_reports(): void
    {
        $this->actingAsRole('contador')
            ->get('/reports')
            ->assertOk();
    }

    public function test_vendedor_can_view_products_but_cannot_create(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)
            ->get('/products')
            ->assertOk();

        $this->assertFalse($vendedor->can('products.create'));
    }

    public function test_vendedor_cannot_view_reports_even_though_they_can_view_products(): void
    {
        $this->actingAsRole('vendedor')
            ->get('/products')
            ->assertOk();

        $this->actingAsRole('vendedor')
            ->get('/reports')
            ->assertForbidden();
    }

    public function test_vendedor_can_create_sales(): void
    {
        $this->actingAsRole('vendedor')
            ->get('/sales')
            ->assertOk();
    }

    public function test_comprador_cannot_view_sales(): void
    {
        $this->actingAsRole('comprador')
            ->get('/sales')
            ->assertForbidden();
    }

    public function test_role_label_helper_returns_spanish_label(): void
    {
        $admin = $this->admin();
        $this->assertSame('Administrador', $admin->primaryRoleLabel());

        $vendedor = $this->vendedor();
        $this->assertSame('Vendedor', $vendedor->primaryRoleLabel());
    }

    public function test_role_seeder_creates_all_roles(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'gerente']);
        $this->assertDatabaseHas('roles', ['name' => 'vendedor']);
        $this->assertDatabaseHas('roles', ['name' => 'comprador']);
        $this->assertDatabaseHas('roles', ['name' => 'contador']);
    }

    public function test_admin_role_has_all_permissions(): void
    {
        $admin = $this->admin();
        $this->assertTrue($admin->can('products.create'));
        $this->assertTrue($admin->can('reports.view_any'));
        $this->assertTrue($admin->can('customers.delete'));
    }

    public function test_vendedor_role_lacks_admin_permissions(): void
    {
        $vendedor = $this->vendedor();
        $this->assertFalse($vendedor->can('products.create'));
        $this->assertFalse($vendedor->can('purchases.create'));
        $this->assertTrue($vendedor->can('sales.create'));
    }
}
