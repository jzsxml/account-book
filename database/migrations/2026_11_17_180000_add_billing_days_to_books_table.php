<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedTinyInteger('salary_day')->nullable()->after('timezone');
            $table->unsignedTinyInteger('huabei_billing_day')->nullable()->after('salary_day');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['salary_day', 'huabei_billing_day']);
        });
    }
};