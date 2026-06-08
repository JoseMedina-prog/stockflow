<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->dateTime('occurred_at');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('outcome')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index('type');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
