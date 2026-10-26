<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table): void {
            $table->string('normalized_phone', 30)->nullable()->index()->after('phone');
        });

        Schema::create('families', function (Blueprint $table): void {
            $table->id();
            $table->string('family_code')->nullable()->unique();
            $table->string('display_name');
            $table->foreignId('primary_guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            $table->string('phone', 30)->nullable()->index();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('family_guardian', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->string('relationship_role', 50)->default('guardian');
            $table->boolean('is_primary')->default(false);
            $table->boolean('authorized_fee_payer')->default(false);
            $table->timestamps();
            $table->unique(['family_id', 'guardian_id']);
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreignId('family_id')->nullable()->after('guardian_id')->constrained('families')->nullOnDelete();
            $table->unsignedSmallInteger('sibling_order')->nullable()->after('family_id');
        });

        Schema::create('student_guardian', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->string('relationship_role', 50)->default('guardian');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'guardian_id']);
        });

        Schema::create('sibling_discount_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('discount_type', 30);
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->unsignedSmallInteger('minimum_sibling_order')->default(2);
            $table->foreignId('fee_category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->foreignId('sibling_discount_rule_id')->nullable()->after('academic_session_id')->constrained()->nullOnDelete();
            $table->decimal('sibling_discount', 12, 2)->default(0)->after('discount');
        });

        Schema::create('family_statements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('statement_number')->nullable()->unique();
            $table->date('billing_month')->index();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('total_amount', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('family_statement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('balance_at_issue', 12, 2);
            $table->timestamps();
            $table->unique(['family_statement_id', 'fee_invoice_id'], 'family_statement_invoice_unique');
        });

        Schema::create('family_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->string('receipt_number')->nullable()->unique();
            $table->uuid('idempotency_key')->unique();
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->string('reference_number')->nullable();
            $table->date('payment_date')->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('completed')->index();
            $table->text('notes')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('family_payment_id')->nullable()->after('fee_invoice_id')->constrained()->nullOnDelete();
            $table->string('status', 20)->default('completed')->after('reference_number')->index();
        });

        Schema::create('family_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['family_payment_id', 'fee_invoice_id'], 'family_payment_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_payment_allocations');
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('family_payment_id');
            $table->dropColumn('status');
        });
        Schema::dropIfExists('family_payments');
        Schema::dropIfExists('family_statement_items');
        Schema::dropIfExists('family_statements');
        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sibling_discount_rule_id');
            $table->dropColumn('sibling_discount');
        });
        Schema::dropIfExists('sibling_discount_rules');
        Schema::dropIfExists('student_guardian');
        Schema::table('students', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('family_id');
            $table->dropColumn('sibling_order');
        });
        Schema::dropIfExists('family_guardian');
        Schema::dropIfExists('families');
        Schema::table('guardians', function (Blueprint $table): void {
            $table->dropColumn('normalized_phone');
        });
    }
};
