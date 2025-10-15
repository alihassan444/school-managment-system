<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $columns = [
                'fees', 'books', 'other', 'previous_balance', 'discount', 'total', 'paid', 'remaining'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'fees')) {
                $table->decimal('fees', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('students', 'books')) {
                $table->decimal('books', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('students', 'other')) {
                $table->decimal('other', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('students', 'previous_balance')) {
                $table->decimal('previous_balance', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('students', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('students', 'total')) {
                $table->decimal('total', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('students', 'paid')) {
                $table->decimal('paid', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('students', 'remaining')) {
                $table->decimal('remaining', 10, 2)->default(0);
            }
        });
    }
};


