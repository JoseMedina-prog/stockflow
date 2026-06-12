<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('folio')->nullable()->after('id')->unique();
        });

        $sales = DB::table('sales')->orderBy('id')->get();
        $used = [];
        $counter = 1;

        foreach ($sales as $sale) {
            do {
                $candidate = 'V-'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT);
                $counter++;
            } while (in_array($candidate, $used, true));

            $used[] = $candidate;
            DB::table('sales')->where('id', $sale->id)->update(['folio' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['folio']);
            $table->dropColumn('folio');
        });
    }
};
