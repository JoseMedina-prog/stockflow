<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['sale_date', 'balance'], 'sales_sale_date_balance_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['deleted_at', 'balance'], 'purchases_deleted_at_balance_index');
            $table->index(['deleted_at', 'purchase_date'], 'purchases_deleted_at_purchase_date_index');
            $table->index(['deleted_at', 'status'], 'purchases_deleted_at_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['stock', 'min_stock'], 'products_stock_min_stock_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('created_at', 'stock_movements_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_sale_date_balance_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_deleted_at_balance_index');
            $table->dropIndex('purchases_deleted_at_purchase_date_index');
            $table->dropIndex('purchases_deleted_at_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_stock_min_stock_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_created_at_index');
        });
    }
};
