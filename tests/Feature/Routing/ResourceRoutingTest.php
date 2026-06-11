<?php

namespace Tests\Feature\Routing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ResourceRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_resources_with_except_show_apply_view_any_permission(): void
    {
        foreach ([
            'categories', 'products', 'customers', 'suppliers', 'leads',
            'opportunities', 'quotes', 'tasks', 'taxes',
        ] as $name) {
            $index = route("{$name}.index");
            $this->assertNotEmpty($index, "Route {$name}.index should exist");

            $routes = Route::getRoutes();
            $indexRoute = $routes->getByName("{$name}.index");
            $this->assertNotNull($indexRoute);
            $this->assertContains(
                "permission:{$name}.view_any",
                $indexRoute->gatherMiddleware(),
                "Route {$name}.index must require permission {$name}.view_any",
            );
        }
    }

    public function test_purchases_excludes_show_and_destroy(): void
    {
        $routes = Route::getRoutes();
        $this->assertNotNull($routes->getByName('purchases.index'));
        $this->assertNotNull($routes->getByName('purchases.create'));
        $this->assertNotNull($routes->getByName('purchases.store'));
        $this->assertNotNull($routes->getByName('purchases.edit'));
        $this->assertNotNull($routes->getByName('purchases.update'));
        $this->assertNull($routes->getByName('purchases.show'), 'purchases.show should be excluded');
        $this->assertNull($routes->getByName('purchases.destroy'), 'purchases.destroy should be excluded');
    }

    public function test_sales_includes_show_but_excludes_edit_update_destroy(): void
    {
        $routes = Route::getRoutes();
        $this->assertNotNull($routes->getByName('sales.index'));
        $this->assertNotNull($routes->getByName('sales.create'));
        $this->assertNotNull($routes->getByName('sales.store'));
        $this->assertNotNull($routes->getByName('sales.show'));
        $this->assertNull($routes->getByName('sales.edit'), 'sales.edit should be excluded');
        $this->assertNull($routes->getByName('sales.update'), 'sales.update should be excluded');
        $this->assertNull($routes->getByName('sales.destroy'), 'sales.destroy should be excluded');
    }

    public function test_returns_uses_sale_return_parameter(): void
    {
        $routes = Route::getRoutes();
        $showRoute = $routes->getByName('returns.show');
        $this->assertNotNull($showRoute);
        $uri = $showRoute->uri();
        $this->assertStringContainsString('{saleReturn}', $uri);
        $this->assertStringNotContainsString('{returns}', $uri);
    }
}
