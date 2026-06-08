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

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view_any')
        ->name('dashboard');

    Route::resource('categories', CategoryController::class)
        ->except(['show'])
        ->middleware('permission:categories.view_any');

    Route::resource('products', ProductController::class)
        ->except(['show'])
        ->middleware('permission:products.view_any');

    Route::resource('customers', CustomerController::class)
        ->except(['show'])
        ->middleware('permission:customers.view_any');

    Route::get('customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:customers.view_any')
        ->name('customers.show');

    Route::resource('suppliers', SupplierController::class)
        ->except(['show'])
        ->middleware('permission:suppliers.view_any');

    Route::resource('purchases', PurchaseController::class)
        ->except(['show'])
        ->middleware('permission:purchases.view_any');

    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
        ->middleware('permission:purchases.update')
        ->name('purchases.receive');

    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
        ->middleware('permission:purchases.update')
        ->name('purchases.cancel');

    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])
        ->middleware('permission:purchases.view_any')
        ->name('purchases.show');

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

    Route::resource('leads', LeadController::class)
        ->except(['show'])
        ->middleware('permission:leads.view_any');

    Route::get('leads/{lead}', [LeadController::class, 'show'])
        ->middleware('permission:leads.view_any')
        ->name('leads.show');

    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])
        ->middleware('permission:leads.update')
        ->name('leads.convert');

    Route::post('leads/{lead}/mark-lost', [LeadController::class, 'markLost'])
        ->middleware('permission:leads.update')
        ->name('leads.mark-lost');

    Route::resource('opportunities', OpportunityController::class)
        ->except(['show'])
        ->middleware('permission:opportunities.view_any');

    Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show'])
        ->middleware('permission:opportunities.view_any')
        ->name('opportunities.show');

    Route::post('opportunities/{opportunity}/advance', [OpportunityController::class, 'advance'])
        ->middleware('permission:opportunities.update')
        ->name('opportunities.advance');

    Route::resource('quotes', QuoteController::class)
        ->except(['show'])
        ->middleware('permission:quotes.view_any');

    Route::get('quotes/{quote}', [QuoteController::class, 'show'])
        ->middleware('permission:quotes.view_any')
        ->name('quotes.show');

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

    Route::resource('tasks', TaskController::class)
        ->except(['show'])
        ->middleware('permission:tasks.view_any');

    Route::get('tasks/{task}', [TaskController::class, 'show'])
        ->middleware('permission:tasks.view_any')
        ->name('tasks.show');

    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])
        ->middleware('permission:tasks.update')
        ->name('tasks.complete');

    Route::get('activities', [ActivityController::class, 'index'])
        ->middleware('permission:activities.view_any')
        ->name('activities.index');

    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])
        ->middleware('permission:activities.update')
        ->name('activities.destroy');

    Route::post('customers/{customer}/activities', [ActivityController::class, 'storeForCustomer'])
        ->middleware('permission:activities.create')
        ->name('customers.activities.store');

    Route::post('leads/{lead}/activities', [ActivityController::class, 'storeForLead'])
        ->middleware('permission:activities.create')
        ->name('leads.activities.store');

    Route::post('opportunities/{opportunity}/activities', [ActivityController::class, 'storeForOpportunity'])
        ->middleware('permission:activities.create')
        ->name('opportunities.activities.store');

    Route::post('sales/{sale}/activities', [ActivityController::class, 'storeForSale'])
        ->middleware('permission:activities.create')
        ->name('sales.activities.store');

    Route::get('search', SearchController::class)->name('search');

    Route::prefix('accounting')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('accounting.index');
        Route::get('ledger', [AccountingController::class, 'ledger'])->name('accounting.ledger');
        Route::get('trial-balance', [AccountingController::class, 'trialBalance'])->name('accounting.trial-balance');
        Route::get('income-statement', [AccountingController::class, 'incomeStatement'])->name('accounting.income-statement');
        Route::get('balance-sheet', [AccountingController::class, 'balanceSheet'])->name('accounting.balance-sheet');
        Route::get('tax-report', [AccountingController::class, 'taxReport'])->name('accounting.tax-report');
        Route::get('accounts', [AccountingController::class, 'accounts'])->name('accounting.accounts');
    })->middleware('permission:accounting.view_any');

    Route::resource('taxes', TaxController::class)
        ->except(['show'])
        ->middleware('permission:taxes.view_any');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
