<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->uuid('idempotency_key')->nullable()->unique()->after('family_payment_id');
        });

        Schema::table('fee_invoice_items', function (Blueprint $table): void {
            $table->date('charge_date')->nullable()->after('description')->index();
            $table->date('billing_month')->nullable()->after('charge_date')->index();
            $table->string('item_type', 30)->default('charge')->after('billing_month');
            $table->string('status', 20)->default('active')->after('item_type')->index();
            $table->foreignId('source_item_id')->nullable()->after('status')->constrained('fee_invoice_items')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('amount')->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
        });

        Schema::create('fee_invoice_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_invoice_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('adjustment_type', 30)->index();
            $table->decimal('original_amount', 12, 2)->nullable();
            $table->decimal('updated_amount', 12, 2)->nullable();
            $table->decimal('difference_amount', 12, 2)->default(0);
            $table->json('original_values')->nullable();
            $table->json('updated_values')->nullable();
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('student_fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('monthly_fee', 12, 2);
            $table->date('effective_from')->index();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'effective_from']);
        });

        DB::table('fee_invoice_items')->orderBy('id')->chunkById(200, function ($items): void {
            $invoices = DB::table('fee_invoices')->whereIn('id', $items->pluck('fee_invoice_id'))->get()->keyBy('id');
            foreach ($items as $item) {
                $invoice = $invoices->get($item->fee_invoice_id);
                DB::table('fee_invoice_items')->where('id', $item->id)->update([
                    'charge_date' => $invoice?->issued_on ?: $invoice?->billing_month,
                    'billing_month' => $invoice?->billing_month,
                ]);
            }
        });

        $categories = [
            ['name' => 'Examination Fee', 'code' => 'examination'],
            ['name' => 'Admission Fee', 'code' => 'admission'],
            ['name' => 'Registration Fee', 'code' => 'registration'],
            ['name' => 'Annual Fund', 'code' => 'annual-fund'],
            ['name' => 'Sports Fund', 'code' => 'sports-fund'],
            ['name' => 'Event Fee', 'code' => 'event'],
            ['name' => 'Academy Fee', 'code' => 'academy'],
            ['name' => 'Transport Fee', 'code' => 'transport'],
            ['name' => 'Miscellaneous Charges', 'code' => 'miscellaneous'],
        ];
        foreach ($categories as $category) {
            DB::table('fee_categories')->updateOrInsert(['code' => $category['code']], $category + [
                'is_recurring' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_structures');
        Schema::dropIfExists('fee_invoice_adjustments');
        Schema::table('fee_invoice_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('source_item_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['charge_date', 'billing_month', 'item_type', 'status', 'reversed_at', 'reversal_reason']);
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
