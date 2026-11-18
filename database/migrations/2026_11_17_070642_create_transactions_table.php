<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->dateTime('occurred_at');
            $table->date('occurred_date');
            $table->string('note', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'occurred_at']);
            $table->index(['book_id', 'occurred_date', 'type']);
            $table->index(['book_id', 'type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};