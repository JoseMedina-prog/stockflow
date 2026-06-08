<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source', 30)->default('other');
            $table->string('stage', 20)->default('new');
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->tinyInteger('score')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('converted_at')->nullable();
            $table->foreignId('converted_to_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->text('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('stage');
            $table->index('owner_id');
            $table->index('source');
            $table->index('converted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
