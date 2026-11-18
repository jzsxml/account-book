<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_billing_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('label', 64);
            $table->string('kind', 16); // income | expense
            $table->unsignedTinyInteger('day');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['book_id', 'sort']);
        });

        if (Schema::hasColumn('books', 'salary_day')) {
            $books = DB::table('books')
                ->select('id', 'salary_day', 'huabei_billing_day')
                ->get();

            foreach ($books as $book) {
                $sort = 0;
                if ($book->salary_day) {
                    DB::table('book_billing_schedules')->insert([
                        'book_id' => $book->id,
                        'label' => '工资入账',
                        'kind' => 'income',
                        'day' => $book->salary_day,
                        'sort' => $sort++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                if ($book->huabei_billing_day) {
                    DB::table('book_billing_schedules')->insert([
                        'book_id' => $book->id,
                        'label' => '花呗扣款',
                        'kind' => 'expense',
                        'day' => $book->huabei_billing_day,
                        'sort' => $sort++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('books', function (Blueprint $table) {
                $table->dropColumn(['salary_day', 'huabei_billing_day']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedTinyInteger('salary_day')->nullable()->after('timezone');
            $table->unsignedTinyInteger('huabei_billing_day')->nullable()->after('salary_day');
        });

        Schema::dropIfExists('book_billing_schedules');
    }
};