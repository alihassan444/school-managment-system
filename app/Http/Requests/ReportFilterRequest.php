<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public const TYPES = [
        'fee_collection', 'fee_status', 'outstanding_dues', 'family_fees',
        'monthly_attendance', 'student_attendance', 'students', 'exams',
        'student_performance', 'staff_salary', 'timetable',
    ];

    public function authorize(): bool
    {
        if ($this->input('report_type') === 'staff_salary') {
            return ($this->user()?->role ?: 'admin') === 'admin';
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('report_type', 'fee_collection');
        $defaultMode = match ($type) {
            'monthly_attendance', 'staff_salary' => 'monthly',
            'students', 'exams', 'student_performance', 'timetable' => 'all',
            default => ($this->filled('from') || $this->filled('to')) ? 'range' : 'monthly',
        };

        $this->merge([
            'report_type' => $type,
            'period_mode' => $this->input('period_mode', $defaultMode),
            'month' => $this->input('month', now()->format('Y-m')),
        ]);
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::in(self::TYPES)],
            'period_mode' => ['required', Rule::in(['all', 'monthly', 'range'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'academic_session_id' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'family_id' => ['nullable', 'integer', 'exists:families,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'status' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.after_or_equal' => 'The report end date must be on or after the start date.',
        ];
    }
}
