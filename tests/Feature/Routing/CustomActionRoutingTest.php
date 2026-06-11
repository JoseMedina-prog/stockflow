<?php

namespace Tests\Feature\Routing;

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomActionRoutingTest extends TestCase
{
    use RefreshDatabase;

    public static function controllerProvider(): array
    {
        return [
            'PurchaseController' => [PurchaseController::class],
            'QuoteController' => [QuoteController::class],
            'LeadController' => [LeadController::class],
            'OpportunityController' => [OpportunityController::class],
            'TaskController' => [TaskController::class],
            'ActivityController' => [ActivityController::class],
            'PaymentController' => [PaymentController::class],
            'SaleReturnController' => [SaleReturnController::class],
        ];
    }

    #[DataProvider('controllerProvider')]
    public function test_every_custom_action_has_required_route_metadata(string $controller): void
    {
        $actions = $controller::CUSTOM_ACTIONS;
        $this->assertNotEmpty($actions, "{$controller} must declare CUSTOM_ACTIONS");

        foreach ($actions as $row) {
            [$action, $method, $uri, $ability, $name] = $row;

            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull(
                $route,
                "Missing route name '{$name}' for {$controller}@{$action}",
            );

            $this->assertSame(strtoupper($method), $route->methods()[0]);

            $this->assertContains(
                "permission:{$ability}",
                $route->gatherMiddleware(),
                "Route '{$name}' must require permission:{$ability}",
            );
        }
    }

    public function test_known_purchase_actions(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('purchases.receive'));
        $this->assertNotNull(Route::getRoutes()->getByName('purchases.cancel'));
    }

    public function test_known_quote_actions(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('quotes.send'));
        $this->assertNotNull(Route::getRoutes()->getByName('quotes.accept'));
        $this->assertNotNull(Route::getRoutes()->getByName('quotes.reject'));
        $this->assertNotNull(Route::getRoutes()->getByName('quotes.convert'));
    }

    public function test_known_lead_actions(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('leads.convert'));
        $this->assertNotNull(Route::getRoutes()->getByName('leads.mark-lost'));
    }

    public function test_returns_actions_use_inferred_returns_prefix(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('returns.approve'));
        $this->assertNotNull(Route::getRoutes()->getByName('returns.reject'));
        $this->assertNull(
            Route::getRoutes()->getByName('sale-returns.approve'),
            'Legacy sale-returns.approve name should be removed',
        );
    }

    public function test_quote_convert_requires_sales_create_not_quotes_update(): void
    {
        $route = Route::getRoutes()->getByName('quotes.convert');
        $this->assertNotNull($route);
        $this->assertContains('permission:sales.create', $route->gatherMiddleware());
    }
}
