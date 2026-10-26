<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique();
            $table->string('name');
            $table->string('role')->default('teacher')->index();
            $table->string('department')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->date('joined_on')->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['school_class_id', 'name']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->unsignedSmallInteger('max_marks')->default(100);
            $table->unsignedSmallInteger('pass_marks')->default(40);
            $table->timestamps();
            $table->unique(['school_class_id', 'subject_id']);
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('relation')->default('father');
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('cnic')->nullable();
            $table->string('occupation')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email')->index();
            $table->boolean('is_active')->default(true)->after('role')->index();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('admission_no')->nullable()->unique()->after('id');
            $table->string('roll_no')->nullable()->index()->after('admission_no');
            $table->date('date_of_birth')->nullable()->after('father_name');
            $table->string('gender', 20)->nullable()->after('date_of_birth');
            $table->date('admission_date')->nullable()->after('gender');
            $table->string('photo_path')->nullable()->after('address');
            $table->foreignId('academic_session_id')->nullable()->after('campus')->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->after('academic_session_id')->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
            $table->foreignId('guardian_id')->nullable()->after('section_id')->constrained()->nullOnDelete();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('roll_no')->nullable();
            $table->string('status')->default('enrolled');
            $table->date('enrolled_on')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'academic_session_id']);
        });

        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('billing_month')->index();
            $table->date('due_date')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->string('status')->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'billing_month']);
        });

        Schema::create('fee_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('fee_invoice_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->string('payment_method')->default('cash')->after('amount');
            $table->string('reference_number')->nullable()->after('payment_method');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date')->index();
            $table->string('status')->default('present')->index();
            $table->string('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'attendance_date']);
        });

        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->date('exam_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedSmallInteger('max_marks')->default(100);
            $table->unsignedSmallInteger('pass_marks')->default(40);
            $table->timestamps();
            $table->unique(['exam_id', 'subject_id']);
        });

        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('marks_obtained', 6, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->unique(['exam_subject_id', 'student_id']);
        });

        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week')->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('salary_month')->index();
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2);
            $table->date('paid_on')->nullable();
            $table->string('status')->default('unpaid')->index();
            $table->string('reference_number')->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'salary_month']);
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        DB::table('academic_sessions')->insert([
            'name' => '2026-2027',
            'starts_on' => '2026-04-01',
            'ends_on' => '2027-03-31',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fee_categories')->insert([
            ['name' => 'Monthly Fee', 'code' => 'monthly', 'is_recurring' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Uniform', 'code' => 'uniform', 'is_recurring' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Books', 'code' => 'books', 'is_recurring' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Paper Fund', 'code' => 'paper-fund', 'is_recurring' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other', 'code' => 'other', 'is_recurring' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('school_settings')->insert([
            ['group' => 'general', 'key' => 'school_name', 'value' => 'Al Suffaa School', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'general', 'key' => 'currency', 'value' => 'PKR', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'fees', 'key' => 'invoice_due_day', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'academics', 'key' => 'passing_percentage', 'value' => '40', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('school_settings');
        Schema::dropIfExists('salary_records');
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('exam_marks');
        Schema::dropIfExists('exam_subjects');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('attendance_records');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_invoice_id');
            $table->dropColumn(['payment_method', 'reference_number']);
        });

        Schema::dropIfExists('fee_invoice_items');
        Schema::dropIfExists('fee_invoices');
        Schema::dropIfExists('fee_categories');
        Schema::dropIfExists('enrollments');

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guardian_id');
            $table->dropConstrainedForeignId('section_id');
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropConstrainedForeignId('academic_session_id');
            $table->dropColumn(['admission_no', 'roll_no', 'date_of_birth', 'gender', 'admission_date', 'photo_path']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });

        Schema::dropIfExists('guardians');
        Schema::dropIfExists('class_subject');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('academic_sessions');
    }
};
