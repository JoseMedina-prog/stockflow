<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaxController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::any('/_boost/browser-logs', fn () => response()->noContent());

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view_any')
        ->name('dashboard');

    foreach ([
        'categories' => CategoryController::class,
        'products' => ProductController::class,
        'customers' => CustomerController::class,
        'suppliers' => SupplierController::class,
        'leads' => LeadController::class,
        'opportunities' => OpportunityController::class,
        'quotes' => QuoteController::class,
        'tasks' => TaskController::class,
        'taxes' => TaxController::class,
    ] as $name => $controller) {
        Route::permissionResource($name, $controller, except: ['show']);
    }

    Route::permissionResource('purchases', PurchaseController::class, except: ['show', 'destroy']);
    Route::permissionResource('sales', SaleController::class, only: ['index', 'create', 'store', 'show']);
    Route::permissionResource('returns', SaleReturnController::class, only: ['index', 'show'], param: 'saleReturn');
    Route::permissionResource('payments', PaymentController::class, only: ['index', 'destroy']);

    foreach ([
        PurchaseController::class,
        QuoteController::class,
        LeadController::class,
        OpportunityController::class,
        TaskController::class,
        ActivityController::class,
        PaymentController::class,
        SaleReturnController::class,
    ] as $controller) {
        foreach ($controller::CUSTOM_ACTIONS as [$action, $method, $uri, $ability, $name]) {
            Route::permissionAction($uri, $action, $method, $controller, $ability, $name);
        }
    }

    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view_any')
        ->name('reports.index');

    Route::get('stock-movements', [StockMovementController::class, 'index'])
        ->middleware('permission:stock_movements.view_any')
        ->name('stock-movements.index');

    Route::get('search', SearchController::class)->name('search');

    Route::prefix('accounting')->middleware('permission:accounting.view_any')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('accounting.index');

        foreach ([
            'ledger' => 'ledger',
            'trial-balance' => 'trialBalance',
            'income-statement' => 'incomeStatement',
            'balance-sheet' => 'balanceSheet',
            'tax-report' => 'taxReport',
            'accounts' => 'accounts',
        ] as $uri => $action) {
            Route::get($uri, [AccountingController::class, $action])->name("accounting.{$uri}");
        }
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
