<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_pagination_uses_bootstrap_markup_without_svg_arrows(): void
    {
        foreach (range(1, 16) as $number) {
            Student::create([
                'name' => "Student {$number}",
                'father_name' => "Parent {$number}",
                'class' => 'Grade 5',
                'campus' => 'campus_1',
                'status' => 'active',
                'monthly_fee' => 5000,
            ]);
        }

        $response = $this->actingAs(User::factory()->create())->get(route('students.index'));

        $response
            ->assertOk()
            ->assertSee('class="pagination"', false)
            ->assertDontSee('<svg', false);
    }
}
