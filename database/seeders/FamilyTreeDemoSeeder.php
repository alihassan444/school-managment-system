<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FamilyTreeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $families = [
            [
                'code' => 'FAM-DMO-01', 'name' => 'Ahmed Family', 'address' => 'Gulshan Town, Lahore',
                'father' => ['Imran Ahmed', '03017710001', 'Engineer'],
                'mother' => ['Saba Ahmed', '03027710001', 'Teacher'],
                'children' => ['03009900001', '03009900011', '03009900015'],
                'extra' => ['Shamim Begum', '03037710001', 'grandparent'],
            ],
            [
                'code' => 'FAM-DMO-02', 'name' => 'Khan Family', 'address' => 'Rana Town, Lahore',
                'father' => ['Faisal Khan', '03017710002', 'Business Owner'],
                'mother' => ['Nadia Khan', '03027710002', 'Doctor'],
                'children' => ['03009900004', '03009900014'],
            ],
            [
                'code' => 'FAM-DMO-03', 'name' => 'Noor Family', 'address' => 'Johar Town, Lahore',
                'father' => ['Rashid Noor', '03017710003', 'Government Service'],
                'mother' => ['Samina Noor', '03027710003', 'Homemaker'],
                'children' => ['03009900002', '03009900026'],
            ],
            [
                'code' => 'FAM-DMO-04', 'name' => 'Raza Family', 'address' => 'Iqbal Town, Lahore',
                'father' => ['Asif Raza', '03017710004', 'Architect'],
                'mother' => ['Amina Raza', '03027710004', 'Lecturer'],
                'children' => ['03009900005', '03009900010', '03009900016'],
            ],
            [
                'code' => 'FAM-DMO-05', 'name' => 'Qureshi Family', 'address' => 'Wapda Town, Lahore',
                'father' => ['Omer Qureshi', '03017710005', 'Banker'],
                'mother' => ['Hira Qureshi', '03027710005', 'Designer'],
                'children' => ['03009900017', '03009900023'],
            ],
            [
                'code' => 'FAM-DMO-06', 'name' => 'Iqbal Family', 'address' => 'Model Town, Lahore',
                'father' => ['Javed Iqbal', '03017710006', 'Accountant'],
                'mother' => ['Farah Iqbal', '03027710006', 'Teacher'],
                'children' => ['03009900008', '03009900030'],
            ],
        ];

        DB::transaction(function () use ($families): void {
            foreach ($families as $index => $row) {
                $father = $this->guardian($row['father'], 'father', $row['address'], $index + 1);
                $mother = $this->guardian($row['mother'], 'mother', $row['address'], $index + 21);

                $family = Family::updateOrCreate(
                    ['family_code' => $row['code']],
                    [
                        'display_name' => $row['name'],
                        'primary_guardian_id' => $father->id,
                        'phone' => $father->phone,
                        'address' => $row['address'],
                        'notes' => 'Demo family tree data',
                    ]
                );

                $familyGuardians = [
                    $father->id => ['relationship_role' => 'father', 'is_primary' => true, 'authorized_fee_payer' => true],
                    $mother->id => ['relationship_role' => 'mother', 'is_primary' => false, 'authorized_fee_payer' => true],
                ];

                if (isset($row['extra'])) {
                    [$name, $phone, $relation] = $row['extra'];
                    $extra = Guardian::updateOrCreate(
                        ['phone' => $phone],
                        ['name' => $name, 'relation' => $relation, 'address' => $row['address'], 'occupation' => 'Retired']
                    );
                    $familyGuardians[$extra->id] = [
                        'relationship_role' => $relation, 'is_primary' => false, 'authorized_fee_payer' => true,
                    ];
                }

                $family->guardians()->sync($familyGuardians);

                Student::whereIn('mobile', $row['children'])
                    ->where(fn ($query) => $query->whereNull('family_id')->orWhere('family_id', $family->id))
                    ->orderBy('date_of_birth')
                    ->orderBy('id')
                    ->get()
                    ->each(function (Student $student, int $studentIndex) use ($family, $father, $mother, $row): void {
                        $student->update([
                            'family_id' => $family->id,
                            'guardian_id' => $father->id,
                            'father_name' => $father->name,
                            'address' => $row['address'],
                            'sibling_order' => $studentIndex + 1,
                        ]);
                        $student->guardians()->sync([
                            $father->id => ['relationship_role' => 'father', 'is_primary' => true],
                            $mother->id => ['relationship_role' => 'mother', 'is_primary' => false],
                        ]);
                    });
            }
        });

        $this->command?->info('Family-tree demo data ready: 6 families, 14 linked siblings, 13 guardians.');
    }

    private function guardian(array $data, string $relation, string $address, int $number): Guardian
    {
        [$name, $phone, $occupation] = $data;

        return Guardian::updateOrCreate(
            ['phone' => $phone],
            [
                'name' => $name,
                'relation' => $relation,
                'alternate_phone' => '042-389'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'cnic' => '35202-'.str_pad((string) (8100000 + $number), 7, '0', STR_PAD_LEFT).'-'.(($number % 9) + 1),
                'occupation' => $occupation,
                'email' => strtolower(str_replace(' ', '.', $name)).'@family.demo',
                'address' => $address,
            ]
        );
    }
}
