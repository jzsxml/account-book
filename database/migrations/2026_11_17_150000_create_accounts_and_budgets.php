<?php

use App\Models\Book;
use App\Services\AccountProvisioner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['cash', 'ewallet', 'bank', 'other'])->default('other');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['book_id', 'sort']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->after('book_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::create('book_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['book_id', 'year', 'month']);
        });

        $provisioner = app(AccountProvisioner::class);
        Book::query()->each(fn (Book $book) => $provisioner->provisionForBook($book));
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
        });
        Schema::dropIfExists('book_budgets');
        Schema::dropIfExists('accounts');
    }
};