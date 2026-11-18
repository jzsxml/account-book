<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date');
            $table->decimal('income_total', 15, 2)->default(0);
            $table->decimal('expense_total', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['book_id', 'summary_date']);
            $table->index(['book_id', 'summary_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};