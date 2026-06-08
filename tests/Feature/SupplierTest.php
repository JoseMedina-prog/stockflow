<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_suppliers_index_page_renders(): void
    {
        $this->actingAsAdmin()
            ->get(route('suppliers.index'))
            ->assertOk();
    }

    public function test_suppliers_create_page_renders(): void
    {
        $this->actingAsAdmin()
            ->get(route('suppliers.create'))
            ->assertOk();
    }

    public function test_supplier_can_be_created(): void
    {
        $payload = [
            'name' => 'Distribuidora La Norteña',
            'contact_name' => 'María Pérez',
            'email' => 'contacto@lanortena.mx',
            'phone' => '5512345678',
            'tax_id' => 'DIN150101AAA',
            'address' => 'Av. Insurgentes 123, CDMX',
            'notes' => 'Pago a 30 días',
            'is_active' => true,
        ];

        $response = $this->actingAsAdmin()
            ->post(route('suppliers.store'), $payload);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Distribuidora La Norteña',
            'email' => 'contacto@lanortena.mx',
            'tax_id' => 'DIN150101AAA',
            'is_active' => true,
        ]);
    }

    public function test_supplier_name_is_required(): void
    {
        $this->actingAsAdmin()
            ->post(route('suppliers.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_supplier_email_must_be_unique(): void
    {
        Supplier::factory()->create(['email' => 'taken@example.com']);

        $this->actingAsAdmin()
            ->post(route('suppliers.store'), [
                'name' => 'Test',
                'email' => 'taken@example.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_supplier_update_ignores_own_email_in_unique_rule(): void
    {
        $supplier = Supplier::factory()->create(['email' => 'same@example.com']);

        $this->actingAsAdmin()
            ->put(route('suppliers.update', $supplier), [
                'name' => $supplier->name,
                'email' => 'same@example.com',
                'is_active' => true,
            ])
            ->assertRedirect(route('suppliers.index'));
    }

    public function test_supplier_can_be_soft_deleted(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAsAdmin()
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_inactive_supplier_can_be_filtered(): void
    {
        Supplier::factory()->count(3)->create(['is_active' => true]);
        Supplier::factory()->inactive()->count(2)->create();

        $this->actingAsAdmin()
            ->get(route('suppliers.index', ['status' => 'inactive']))
            ->assertInertia(fn ($page) => $page
                ->component('Suppliers/Index')
                ->has('suppliers.data', 2)
                ->where('suppliers.data.0.is_active', false));
    }

    public function test_supplier_search_works(): void
    {
        Supplier::factory()->create(['name' => 'Distribuidora Norte']);
        Supplier::factory()->create(['name' => 'Otra Empresa']);

        $this->actingAsAdmin()
            ->get(route('suppliers.index', ['search' => 'Norte']))
            ->assertInertia(fn ($page) => $page->has('suppliers.data', 1));
    }

    public function test_vendedor_cannot_access_suppliers_at_all(): void
    {
        $vendedor = $this->vendedor();
        $this->assertFalse($vendedor->can('suppliers.view_any'));
        $this->assertFalse($vendedor->can('suppliers.create'));

        $this->actingAs($vendedor)
            ->get(route('suppliers.index'))
            ->assertForbidden();

        $this->actingAs($vendedor)
            ->get(route('suppliers.create'))
            ->assertForbidden();

        $this->actingAs($vendedor)
            ->post(route('suppliers.store'), ['name' => 'Test'])
            ->assertForbidden();
    }

    public function test_comprador_can_create_supplier(): void
    {
        $this->actingAsRole('comprador')
            ->post(route('suppliers.store'), [
                'name' => 'Distribuidora X',
                'is_active' => true,
            ])
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', ['name' => 'Distribuidora X']);
    }

    public function test_contador_cannot_create_supplier(): void
    {
        $contador = $this->contador();
        $this->assertFalse($contador->can('suppliers.create'));

        $this->actingAs($contador)
            ->post(route('suppliers.store'), ['name' => 'Test'])
            ->assertForbidden();
    }

    public function test_supplier_search_helper_finds_by_tax_id(): void
    {
        Supplier::factory()->create(['tax_id' => 'XAXX010101000']);
        Supplier::factory()->create(['tax_id' => 'XBXX020202000']);

        $results = Supplier::query()->search('XAXX')->get();
        $this->assertCount(1, $results);
        $this->assertSame('XAXX010101000', $results->first()->tax_id);
    }
}
