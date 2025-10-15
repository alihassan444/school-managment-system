<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = [
                'monthly_fee_amount', 'books_amount', 'other_amount', 'previous_balance_amount',
                'payment_method', 'reference_number', 'month_year', 'payment_type'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'monthly_fee_amount')) {
                $table->decimal('monthly_fee_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('payments', 'books_amount')) {
                $table->decimal('books_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('payments', 'other_amount')) {
                $table->decimal('other_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('payments', 'previous_balance_amount')) {
                $table->decimal('previous_balance_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            if (!Schema::hasColumn('payments', 'reference_number')) {
                $table->string('reference_number')->nullable();
            }
            if (!Schema::hasColumn('payments', 'month_year')) {
                $table->string('month_year')->nullable();
            }
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type')->nullable();
            }
        });
    }
};


