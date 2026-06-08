<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->after('subtotal')->constrained('taxes')->nullOnDelete();
            $table->decimal('tax_rate', 6, 4)->default(0)->after('tax_id');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('tax_id')->nullable()->after('line_total')->constrained('taxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropColumn(['tax_id', 'tax_rate', 'tax_amount']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['tax_id']);
            $table->dropColumn(['tax_id']);
        });
    }
};
