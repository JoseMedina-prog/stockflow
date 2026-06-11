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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view_any')
        ->name('dashboard');

    $resourcesExceptShow = [
        'categories'    => CategoryController::class,
        'products'      => ProductController::class,
        'customers'     => CustomerController::class,
        'suppliers'     => SupplierController::class,
        'leads'         => LeadController::class,
        'opportunities' => OpportunityController::class,
        'quotes'        => QuoteController::class,
        'tasks'         => TaskController::class,
        'taxes'         => TaxController::class,
    ];
    foreach ($resourcesExceptShow as $name => $controller) {
        Route::resource($name, $controller)
            ->except(['show'])
            ->middleware('permission:'.$name.'.view_any');
    }

    Route::resource('purchases', PurchaseController::class)
        ->except(['show', 'destroy'])
        ->middleware('permission:purchases.view_any');

    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
        ->middleware('permission:purchases.update')
        ->name('purchases.receive');

    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
        ->middleware('permission:purchases.update')
        ->name('purchases.cancel');

    Route::resource('sales', SaleController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('permission:sales.view_any');

    Route::get('sales/{sale}/returns/create', [SaleReturnController::class, 'create'])
        ->middleware('permission:returns.create')
        ->name('sales.returns.create');

    Route::post('sales/{sale}/returns', [SaleReturnController::class, 'store'])
        ->middleware('permission:returns.create')
        ->name('sales.returns.store');

    Route::resource('returns', SaleReturnController::class)
        ->only(['index', 'show'])
        ->parameters(['returns' => 'saleReturn'])
        ->middleware('permission:returns.view_any');

    Route::post('returns/{saleReturn}/approve', [SaleReturnController::class, 'approve'])
        ->middleware('permission:returns.update')
        ->name('sale-returns.approve');

    Route::post('returns/{saleReturn}/reject', [SaleReturnController::class, 'reject'])
        ->middleware('permission:returns.update')
        ->name('sale-returns.reject');

    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view_any')
        ->name('reports.index');

    Route::get('stock-movements', [StockMovementController::class, 'index'])
        ->middleware('permission:stock_movements.view_any')
        ->name('stock-movements.index');

    Route::resource('payments', PaymentController::class)
        ->only(['index', 'destroy'])
        ->middleware('permission:payments.view_any');

    Route::post('sales/{sale}/payments', [PaymentController::class, 'storeForSale'])
        ->middleware('permission:payments.create')
        ->name('sales.payments.store');

    Route::post('purchases/{purchase}/payments', [PaymentController::class, 'storeForPurchase'])
        ->middleware('permission:payments.create')
        ->name('purchases.payments.store');

    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])
        ->middleware('permission:leads.update')
        ->name('leads.convert');

    Route::post('leads/{lead}/mark-lost', [LeadController::class, 'markLost'])
        ->middleware('permission:leads.update')
        ->name('leads.mark-lost');

    Route::post('opportunities/{opportunity}/advance', [OpportunityController::class, 'advance'])
        ->middleware('permission:opportunities.update')
        ->name('opportunities.advance');

    Route::post('quotes/{quote}/send', [QuoteController::class, 'send'])
        ->middleware('permission:quotes.update')
        ->name('quotes.send');

    Route::post('quotes/{quote}/accept', [QuoteController::class, 'accept'])
        ->middleware('permission:quotes.update')
        ->name('quotes.accept');

    Route::post('quotes/{quote}/reject', [QuoteController::class, 'reject'])
        ->middleware('permission:quotes.update')
        ->name('quotes.reject');

    Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])
        ->middleware('permission:sales.create')
        ->name('quotes.convert');

    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])
        ->middleware('permission:tasks.update')
        ->name('tasks.complete');

    Route::get('activities', [ActivityController::class, 'index'])
        ->middleware('permission:activities.view_any')
        ->name('activities.index');

    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])
        ->middleware('permission:activities.update')
        ->name('activities.destroy');

    $activityTargets = [
        'customers'     => 'storeForCustomer',
        'leads'         => 'storeForLead',
        'opportunities' => 'storeForOpportunity',
        'sales'         => 'storeForSale',
    ];
    foreach ($activityTargets as $segment => $method) {
        Route::post("{$segment}/{{$segment}}/activities", [ActivityController::class, $method])
            ->middleware('permission:activities.create')
            ->name("{$segment}.activities.store");
    }


    Route::get('search', SearchController::class)->name('search');

    Route::prefix('accounting')->middleware('permission:accounting.view_any')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('accounting.index');

        $accountingRoutes = [
            'ledger'           => 'ledger',
            'trial-balance'    => 'trialBalance',
            'income-statement' => 'incomeStatement',
            'balance-sheet'    => 'balanceSheet',
            'tax-report'       => 'taxReport',
            'accounts'         => 'accounts',
        ];
        foreach ($accountingRoutes as $uri => $action) {
            Route::get($uri, [AccountingController::class, $action])->name("accounting.{$uri}");
        }
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
