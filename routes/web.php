<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/student-promotions', [App\Http\Controllers\StudentController::class, 'promotionForm'])->name('students.promotion-form')->middleware('role:admin');
    Route::post('/student-promotions', [App\Http\Controllers\StudentController::class, 'promote'])->name('students.promote')->middleware('role:admin');
    Route::resource('students', App\Http\Controllers\StudentController::class);
    Route::resource('payments', App\Http\Controllers\PaymentController::class)->except(['edit', 'update'])->middleware('role:admin,accountant');

    Route::get('/families', [App\Http\Controllers\FamilyController::class, 'index'])->name('families.index')->middleware('role:admin,accountant');
    Route::post('/families', [App\Http\Controllers\FamilyController::class, 'store'])->name('families.store')->middleware('role:admin');
    Route::get('/families/{family}', [App\Http\Controllers\FamilyController::class, 'show'])->name('families.show')->middleware('role:admin,accountant');
    Route::put('/families/{family}', [App\Http\Controllers\FamilyController::class, 'update'])->name('families.update')->middleware('role:admin');
    Route::post('/families/{family}/students', [App\Http\Controllers\FamilyController::class, 'linkStudent'])->name('families.students.link')->middleware('role:admin');
    Route::delete('/families/{family}/students/{student}', [App\Http\Controllers\FamilyController::class, 'unlinkStudent'])->name('families.students.unlink')->middleware('role:admin');
    Route::post('/families/{family}/guardians', [App\Http\Controllers\FamilyController::class, 'addGuardian'])->name('families.guardians.store')->middleware('role:admin');
    Route::get('/guardians/{guardian}', [App\Http\Controllers\FamilyController::class, 'guardian'])->name('guardians.show')->middleware('role:admin,accountant');

    Route::get('/family-fees', [App\Http\Controllers\FamilyFeeController::class, 'index'])->name('family-fees.index')->middleware('role:admin,accountant');
    Route::post('/family-fees/statements', [App\Http\Controllers\FamilyFeeController::class, 'storeStatement'])->name('family-fees.statements.store')->middleware('role:admin,accountant');
    Route::get('/family-fees/statements/{statement}', [App\Http\Controllers\FamilyFeeController::class, 'showStatement'])->name('family-fees.statements.show')->middleware('role:admin,accountant');
    Route::get('/family-fees/statements/{statement}/pdf', [App\Http\Controllers\FamilyFeeController::class, 'statementPdf'])->name('family-fees.statements.pdf')->middleware('role:admin,accountant');
    Route::post('/family-fees/payments', [App\Http\Controllers\FamilyFeeController::class, 'storePayment'])->name('family-fees.payments.store')->middleware('role:admin,accountant');
    Route::get('/family-fees/receipts/{familyPayment}', [App\Http\Controllers\FamilyFeeController::class, 'showReceipt'])->name('family-fees.receipts.show')->middleware('role:admin,accountant');
    Route::get('/family-fees/receipts/{familyPayment}/pdf', [App\Http\Controllers\FamilyFeeController::class, 'receiptPdf'])->name('family-fees.receipts.pdf')->middleware('role:admin,accountant');
    Route::get('/family-fees/{family}/fee-structure', [App\Http\Controllers\FamilyFeeController::class, 'feeStructure'])->name('family-fees.structure')->middleware('role:admin,accountant');
    Route::get('/family-fees/{family}/fee-structure/pdf', [App\Http\Controllers\FamilyFeeController::class, 'feeStructurePdf'])->name('family-fees.structure.pdf')->middleware('role:admin,accountant');
    Route::post('/family-fees/receipts/{familyPayment}/reverse', [App\Http\Controllers\FamilyFeeController::class, 'reverse'])->name('family-fees.receipts.reverse')->middleware('role:admin');
    Route::get('/sibling-discounts', [App\Http\Controllers\SiblingDiscountController::class, 'index'])->name('sibling-discounts.index')->middleware('role:admin');
    Route::post('/sibling-discounts', [App\Http\Controllers\SiblingDiscountController::class, 'store'])->name('sibling-discounts.store')->middleware('role:admin');
    Route::put('/sibling-discounts/{rule}/toggle', [App\Http\Controllers\SiblingDiscountController::class, 'toggle'])->name('sibling-discounts.toggle')->middleware('role:admin');

    Route::get('/academics', [App\Http\Controllers\AcademicController::class, 'index'])->name('academics.index')->middleware('role:admin,teacher');
    Route::post('/academics/classes', [App\Http\Controllers\AcademicController::class, 'storeClass'])->name('academics.classes.store')->middleware('role:admin');
    Route::delete('/academics/classes/{schoolClass}', [App\Http\Controllers\AcademicController::class, 'destroyClass'])->name('academics.classes.destroy')->middleware('role:admin');
    Route::post('/academics/sections', [App\Http\Controllers\AcademicController::class, 'storeSection'])->name('academics.sections.store')->middleware('role:admin');
    Route::delete('/academics/sections/{section}', [App\Http\Controllers\AcademicController::class, 'destroySection'])->name('academics.sections.destroy')->middleware('role:admin');
    Route::post('/academics/subjects', [App\Http\Controllers\AcademicController::class, 'storeSubject'])->name('academics.subjects.store')->middleware('role:admin');
    Route::delete('/academics/subjects/{subject}', [App\Http\Controllers\AcademicController::class, 'destroySubject'])->name('academics.subjects.destroy')->middleware('role:admin');
    Route::post('/academics/assign-subject', [App\Http\Controllers\AcademicController::class, 'assignSubject'])->name('academics.assign-subject')->middleware('role:admin');

    Route::get('/fees', [App\Http\Controllers\FeeController::class, 'index'])->name('fees.index')->middleware('role:admin,accountant');
    Route::post('/fees/generate', [App\Http\Controllers\FeeController::class, 'generate'])->name('fees.generate')->middleware('role:admin,accountant');
    Route::post('/fees/invoices', [App\Http\Controllers\FeeController::class, 'storeInvoice'])->name('fees.invoices.store')->middleware('role:admin');
    Route::put('/fees/invoices/{invoice}', [App\Http\Controllers\FeeController::class, 'updateInvoice'])->name('fees.invoices.update')->middleware('role:admin');
    Route::post('/fees/invoices/{invoice}/charges', [App\Http\Controllers\FeeController::class, 'storeCharge'])->name('fees.charges.store')->middleware('role:admin');
    Route::put('/fees/invoices/{invoice}/charges/{item}', [App\Http\Controllers\FeeController::class, 'updateCharge'])->name('fees.charges.update')->middleware('role:admin');
    Route::post('/fees/invoices/{invoice}/charges/{item}/reverse', [App\Http\Controllers\FeeController::class, 'reverseCharge'])->name('fees.charges.reverse')->middleware('role:admin');
    Route::post('/fees/students/{student}/structures', [App\Http\Controllers\FeeController::class, 'storeFeeStructure'])->name('fees.structures.store')->middleware('role:admin');
    Route::post('/fees/classes/{schoolClass}/structures', [App\Http\Controllers\FeeController::class, 'storeClassFeeStructure'])->name('fees.class-structures.store')->middleware('role:admin');
    Route::get('/fees/settings', [App\Http\Controllers\FeeController::class, 'settings'])->name('fees.settings')->middleware('role:admin');
    Route::put('/fees/settings', [App\Http\Controllers\FeeController::class, 'updateSettings'])->name('fees.settings.update')->middleware('role:admin');
    Route::get('/fees/students/{student}/ledger', [App\Http\Controllers\FeeController::class, 'studentLedger'])->name('fees.students.ledger')->middleware('role:admin,accountant');
    Route::get('/fees/families/{family}/ledger', [App\Http\Controllers\FamilyFeeController::class, 'ledger'])->name('fees.families.ledger')->middleware('role:admin,accountant');
    Route::get('/fees/invoices/{invoice}/pdf', [App\Http\Controllers\FeeController::class, 'pdf'])->name('fees.invoices.pdf')->middleware('role:admin,accountant');
    Route::get('/fees/invoices/{invoice}', [App\Http\Controllers\FeeController::class, 'show'])->name('fees.show')->middleware('role:admin,accountant');

    Route::get('/attendance', [App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index')->middleware('role:admin,teacher');
    Route::post('/attendance', [App\Http\Controllers\AttendanceController::class, 'store'])->name('attendance.store')->middleware('role:admin,teacher');

    Route::resource('exams', App\Http\Controllers\ExamController::class)->only(['index', 'store', 'show', 'destroy'])->middleware('role:admin,teacher');
    Route::post('/exams/{exam}/subjects', [App\Http\Controllers\ExamController::class, 'storeSubject'])->name('exams.subjects.store')->middleware('role:admin,teacher');
    Route::get('/exam-subjects/{examSubject}/marks', [App\Http\Controllers\ExamController::class, 'marks'])->name('exams.marks')->middleware('role:admin,teacher');
    Route::post('/exam-subjects/{examSubject}/marks', [App\Http\Controllers\ExamController::class, 'storeMarks'])->name('exams.marks.store')->middleware('role:admin,teacher');
    Route::get('/exams/{exam}/students/{student}/result-card', [App\Http\Controllers\ExamController::class, 'resultCard'])->name('exams.result-card')->middleware('role:admin,teacher');
    Route::get('/exams/{exam}/students/{student}/roll-slip', [App\Http\Controllers\ExamController::class, 'rollSlip'])->name('exams.roll-slip')->middleware('role:admin,teacher');

    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index')->middleware('role:admin,accountant');
    Route::get('/reports/students', [App\Http\Controllers\ReportController::class, 'students'])->name('reports.students')->middleware('role:admin,accountant');
    Route::get('/reports/print', [App\Http\Controllers\ReportController::class, 'print'])->name('reports.print')->middleware('role:admin,accountant');
    Route::get('/reports/pdf', [App\Http\Controllers\ReportController::class, 'pdf'])->name('reports.pdf')->middleware('role:admin,accountant');
    Route::get('/reports/csv', [App\Http\Controllers\ReportController::class, 'csv'])->name('reports.csv')->middleware('role:admin,accountant');
    Route::get('/reports/families', [App\Http\Controllers\FamilyReportController::class, 'index'])->name('reports.families')->middleware('role:admin,accountant');
    Route::get('/reports/families/pdf', [App\Http\Controllers\FamilyReportController::class, 'pdf'])->name('reports.families.pdf')->middleware('role:admin,accountant');
    Route::get('/reports/families/csv', [App\Http\Controllers\FamilyReportController::class, 'csv'])->name('reports.families.csv')->middleware('role:admin,accountant');

    Route::get('/timetable', [App\Http\Controllers\TimetableController::class, 'index'])->name('timetable.index')->middleware('role:admin,teacher');
    Route::post('/timetable', [App\Http\Controllers\TimetableController::class, 'store'])->name('timetable.store')->middleware('role:admin');
    Route::delete('/timetable/{entry}', [App\Http\Controllers\TimetableController::class, 'destroy'])->name('timetable.destroy')->middleware('role:admin');

    Route::get('/staff', [App\Http\Controllers\StaffController::class, 'index'])->name('staff.index')->middleware('role:admin');
    Route::post('/staff', [App\Http\Controllers\StaffController::class, 'store'])->name('staff.store')->middleware('role:admin');
    Route::delete('/staff/{staff}', [App\Http\Controllers\StaffController::class, 'destroy'])->name('staff.destroy')->middleware('role:admin');
    Route::post('/staff/{staff}/salary', [App\Http\Controllers\StaffController::class, 'salary'])->name('staff.salary.store')->middleware('role:admin');
    Route::get('/salary/{salary}/payslip', [App\Http\Controllers\StaffController::class, 'payslip'])->name('staff.payslip')->middleware('role:admin');

    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index')->middleware('role:admin');
    Route::put('/settings/general', [App\Http\Controllers\SettingsController::class, 'updateGeneral'])->name('settings.general.update')->middleware('role:admin');
    Route::post('/settings/sessions', [App\Http\Controllers\SettingsController::class, 'storeSession'])->name('settings.sessions.store')->middleware('role:admin');
    Route::put('/settings/sessions/{academicSession}/current', [App\Http\Controllers\SettingsController::class, 'currentSession'])->name('settings.sessions.current')->middleware('role:admin');
    Route::post('/settings/fee-categories', [App\Http\Controllers\SettingsController::class, 'storeCategory'])->name('settings.categories.store')->middleware('role:admin');
    Route::post('/settings/users', [App\Http\Controllers\SettingsController::class, 'storeUser'])->name('settings.users.store')->middleware('role:admin');
    Route::put('/settings/users/{user}', [App\Http\Controllers\SettingsController::class, 'updateUser'])->name('settings.users.update')->middleware('role:admin');
    Route::get('/settings/backup', [App\Http\Controllers\SettingsController::class, 'backup'])->name('settings.backup')->middleware('role:admin');
    Route::post('/settings/restore', [App\Http\Controllers\SettingsController::class, 'restore'])->name('settings.restore')->middleware('role:admin');
});
