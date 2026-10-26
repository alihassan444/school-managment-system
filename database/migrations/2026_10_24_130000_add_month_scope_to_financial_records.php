<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->date('issued_on')->nullable()->after('billing_month')->index();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('recorded_by')->nullable()->after('family_payment_id')->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->after('payment_date')->index();
        });

        Schema::table('family_payments', function (Blueprint $table): void {
            $table->date('billing_month')->nullable()->after('family_id')->index();
            $table->string('payment_scope', 30)->default('all_outstanding')->after('billing_month')->index();
        });
    }

    public function down(): void
    {
        Schema::table('family_payments', function (Blueprint $table): void {
            $table->dropIndex(['billing_month']);
            $table->dropIndex(['payment_scope']);
            $table->dropColumn(['billing_month', 'payment_scope']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['posted_at']);
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('posted_at');
        });

        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->dropIndex(['issued_on']);
            $table->dropColumn('issued_on');
        });
    }
};
