<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('monthly_fee_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('uniform_amount', 10, 2)->default(0)->after('monthly_fee_amount');
            $table->decimal('books_amount', 10, 2)->default(0)->after('uniform_amount');
            $table->decimal('paper_fund_amount', 10, 2)->default(0)->after('books_amount');
            $table->decimal('other_amount', 10, 2)->default(0)->after('paper_fund_amount');
            $table->decimal('previous_balance_amount', 10, 2)->default(0)->after('other_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('previous_balance_amount');
            $table->decimal('total_amount', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('remaining_amount', 10, 2)->default(0)->after('total_amount');
        });

        DB::table('payments')->update([
            'monthly_fee_amount' => DB::raw('amount'),
            'total_amount' => DB::raw('amount'),
            'remaining_amount' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_fee_amount',
                'uniform_amount',
                'books_amount',
                'paper_fund_amount',
                'other_amount',
                'previous_balance_amount',
                'discount_amount',
                'total_amount',
                'remaining_amount',
            ]);
        });
    }
};
