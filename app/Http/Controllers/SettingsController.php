<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\FeeCategory;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'settings' => SchoolSetting::pluck('value', 'key'),
            'sessions' => AcademicSession::orderByDesc('starts_on')->get(),
            'categories' => FeeCategory::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'auditLogs' => AuditLog::with('user')->latest()->take(20)->get(),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'school_phone' => ['nullable', 'string', 'max:40'],
            'school_email' => ['nullable', 'email', 'max:150'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', 'max:10'],
            'invoice_due_day' => ['required', 'integer', 'between:1,28'],
            'passing_percentage' => ['required', 'integer', 'between:1,100'],
        ]);

        foreach ($validated as $key => $value) {
            $group = in_array($key, ['invoice_due_day'], true) ? 'fees' : (in_array($key, ['passing_percentage'], true) ? 'academics' : 'general');
            SchoolSetting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $value]);
        }

        return back()->with('success', 'School settings updated.');
    }

    public function storeSession(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:academic_sessions,name'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ]);
        AcademicSession::create($validated + ['is_current' => false]);

        return back()->with('success', 'Academic session added.');
    }

    public function currentSession(AcademicSession $academicSession)
    {
        DB::transaction(function () use ($academicSession): void {
            AcademicSession::query()->update(['is_current' => false]);
            $academicSession->update(['is_current' => true]);
        });

        return back()->with('success', "{$academicSession->name} is now the current session.");
    }

    public function storeCategory(Request $request)
    {
        FeeCategory::create($request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:fee_categories,name'],
            'code' => ['required', 'alpha_dash', 'max:50', 'unique:fee_categories,code'],
            'is_recurring' => ['nullable', 'boolean'],
        ]) + ['is_active' => true]);

        return back()->with('success', 'Fee category added.');
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', 'in:admin,accountant,teacher'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $validated['password'] = Hash::make($validated['password']);
        User::create($validated + ['is_active' => true]);

        return back()->with('success', 'Portal user created.');
    }

    public function updateUser(Request $request, User $user)
    {
        abort_if($user->is(auth()->user()) && ! $request->boolean('is_active'), 422, 'You cannot deactivate your own account.');
        $user->update($request->validate([
            'role' => ['required', Rule::in(['admin', 'accountant', 'teacher'])],
            'is_active' => ['required', 'boolean'],
        ]));

        return back()->with('success', 'User access updated.');
    }

    public function backup()
    {
        $tables = $this->backupTables();
        $payload = [
            'application' => config('app.name'),
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'tables' => collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->map(fn ($row) => (array) $row)->all()]),
        ];

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'school-backup-'.now()->format('Ymd-His').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup' => ['required', 'file', 'mimes:json,txt', 'max:20480'],
            'confirm_restore' => ['accepted'],
        ]);
        $payload = json_decode(file_get_contents($request->file('backup')->getRealPath()), true);
        if (! is_array($payload) || ($payload['version'] ?? null) !== 1 || ! is_array($payload['tables'] ?? null)) {
            return back()->with('error', 'The selected file is not a valid school backup.');
        }

        DB::transaction(function () use ($payload): void {
            foreach ($this->backupTables() as $table) {
                foreach ($payload['tables'][$table] ?? [] as $row) {
                    if (! isset($row['id'])) {
                        continue;
                    }
                    DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                }
            }
        });

        return back()->with('success', 'Backup merged successfully. Existing records not present in the file were preserved.');
    }

    private function backupTables(): array
    {
        return [
            'users', 'academic_sessions', 'staff', 'school_classes', 'sections', 'subjects', 'class_subject',
            'guardians', 'students', 'enrollments', 'fee_categories', 'fee_invoices', 'fee_invoice_items',
            'fee_invoice_adjustments', 'student_fee_structures', 'class_fee_structures',
            'payments', 'attendance_records', 'exams', 'exam_subjects', 'exam_marks', 'timetable_entries',
            'salary_records', 'school_settings', 'audit_logs',
        ];
    }
}
