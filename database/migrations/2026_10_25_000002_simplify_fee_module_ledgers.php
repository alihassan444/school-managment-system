<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->foreignId('family_id')->nullable()->after('student_id')->constrained()->nullOnDelete()->index();
        });

        Schema::create('class_fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->decimal('monthly_fee', 12, 2);
            $table->date('effective_from')->index();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_class_id', 'effective_from']);
        });

        DB::table('fee_invoices')->orderBy('id')->chunkById(200, function ($invoices): void {
            $familyIds = DB::table('students')
                ->whereIn('id', $invoices->pluck('student_id'))
                ->pluck('family_id', 'id');

            foreach ($invoices as $invoice) {
                DB::table('fee_invoices')->where('id', $invoice->id)->update([
                    'family_id' => $familyIds[$invoice->student_id] ?? null,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_fee_structures');
        Schema::table('fee_invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('family_id');
        });
    }
};
