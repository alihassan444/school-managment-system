<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sessionId = DB::table('academic_sessions')->where('is_current', true)->value('id');
        $classNames = DB::table('students')->whereNotNull('class')->where('class', '!=', '')
            ->distinct()->pluck('class')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        foreach ($classNames as $index => $name) {
            $classId = DB::table('school_classes')->where('name', $name)->value('id');
            if (! $classId) {
                $classId = DB::table('school_classes')->insertGetId([
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'monthly_fee' => DB::table('students')->where('class', $name)->avg('monthly_fee') ?: 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $sectionId = DB::table('sections')->where('school_class_id', $classId)->where('name', 'A')->value('id');
            if (! $sectionId) {
                $sectionId = DB::table('sections')->insertGetId([
                    'school_class_id' => $classId,
                    'name' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $students = DB::table('students')->where('class', $name)->get();
            foreach ($students as $student) {
                DB::table('students')->where('id', $student->id)->update([
                    'admission_no' => $student->admission_no ?: 'LEG-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
                    'academic_session_id' => $student->academic_session_id ?: $sessionId,
                    'school_class_id' => $student->school_class_id ?: $classId,
                    'section_id' => $student->section_id ?: $sectionId,
                    'updated_at' => now(),
                ]);

                if ($sessionId) {
                    DB::table('enrollments')->updateOrInsert(
                        ['student_id' => $student->id, 'academic_session_id' => $sessionId],
                        [
                            'school_class_id' => $classId,
                            'section_id' => $sectionId,
                            'roll_no' => $student->roll_no,
                            'status' => 'enrolled',
                            'enrolled_on' => $student->admission_date ?: substr((string) $student->created_at, 0, 10),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // This data normalization is intentionally non-destructive on rollback.
    }
};
