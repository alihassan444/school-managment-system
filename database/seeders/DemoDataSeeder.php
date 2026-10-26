<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $today = today();
        $people = [
            ['Ayaan Ahmed', 'Imran Ahmed'],
            ['Fatima Noor', 'Muhammad Usman'],
            ['Zayan Ali', 'Naveed Ali'],
            ['Hania Khan', 'Faisal Khan'],
            ['Ibrahim Raza', 'Asif Raza'],
            ['Ayesha Siddiqua', 'Salman Siddiqui'],
            ['Rayyan Hassan', 'Hassan Mahmood'],
            ['Maryam Iqbal', 'Javed Iqbal'],
            ['Hamza Tariq', 'Tariq Bashir'],
            ['Inaya Zahra', 'Ali Raza'],
            ['Huzaifa Ahmad', 'Waqar Ahmad'],
            ['Eman Fatima', 'Bilal Akram'],
            ['Abdullah Saeed', 'Saeed Anwar'],
            ['Mahnoor Khan', 'Shahid Khan'],
            ['Yahya Farooq', 'Farooq Ahmed'],
            ['Rida Batool', 'Amjad Hussain'],
            ['Arham Qureshi', 'Omer Qureshi'],
            ['Aleena Aslam', 'Muhammad Aslam'],
            ['Saad Rehman', 'Abdul Rehman'],
            ['Maham Javed', 'Javed Akhtar'],
            ['Musa Khalid', 'Khalid Mehmood'],
            ['Laiba Irfan', 'Irfan Haider'],
            ['Talha Nadeem', 'Nadeem Abbas'],
            ['Zoya Faisal', 'Faisal Shehzad'],
            ['Hammad Akhtar', 'Akhtar Hussain'],
            ['Mehwish Noor', 'Rashid Noor'],
            ['Daniyal Shah', 'Kamran Shah'],
            ['Kinza Rauf', 'Abdul Rauf'],
            ['Ahmed Mustafa', 'Mustafa Kamal'],
            ['Sana Ilyas', 'Muhammad Ilyas'],
        ];
        $classes = [
            'Playgroup', 'Nursery', 'Prep', 'Grade 1', 'Grade 2', 'Grade 3',
            'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9',
        ];
        $monthlyFees = [3500, 3800, 4000, 4500, 4800, 5200, 5500, 6000, 6500, 7000, 7500, 8000];

        DB::transaction(function () use ($today, $people, $classes, $monthlyFees) {
            foreach ($people as $index => [$name, $fatherName]) {
                $number = $index + 1;
                $classIndex = $index % count($classes);
                $createdDaysAgo = $number <= 3 ? 0 : (($number - 3) * 6) % 165;
                $createdAt = $today->copy()->subDays($createdDaysAgo)->setTime(9 + ($index % 5), 15);

                $student = Student::updateOrCreate(
                    ['mobile' => '0300990'.str_pad((string) $number, 4, '0', STR_PAD_LEFT)],
                    [
                        'name' => $name,
                        'father_name' => $fatherName,
                        'class' => $classes[$classIndex],
                        'campus' => $number % 2 === 0 ? 'campus_2' : 'campus_1',
                        'contact' => '042-'.str_pad((string) (3570000 + $number), 7, '0', STR_PAD_LEFT),
                        'address' => ($number % 2 === 0 ? 'Rana Town' : 'Thokar Niaz Baig').', Lahore',
                        'status' => $number % 10 === 0 ? 'inactive' : 'active',
                        'monthly_fee' => $monthlyFees[$classIndex],
                    ]
                );

                $student->forceFill(['created_at' => $createdAt])->saveQuietly();

                foreach (range(5, 0) as $monthsAgo) {
                    if ($monthsAgo === 0 && $student->status === 'inactive') {
                        continue;
                    }

                    if (($number + $monthsAgo) % 5 === 0) {
                        continue;
                    }

                    $month = $today->copy()->subMonths($monthsAgo);
                    $paymentDay = $monthsAgo === 0 && $number <= 8
                        ? $today->day
                        : min(4 + (($number * 3 + $monthsAgo) % 17), $month->daysInMonth);
                    $paymentDate = $month->copy()->day($paymentDay);

                    if ($paymentDate->isAfter($today)) {
                        $paymentDate = $today->copy();
                    }

                    $monthlyFeeAmount = (float) $student->monthly_fee;
                    $uniformAmount = $monthsAgo === 0 && $number % 9 === 0 ? 1200.0 : 0.0;
                    $booksAmount = $monthsAgo === 0 && $number % 7 === 0 ? 900.0 : 0.0;
                    $paperFundAmount = $monthsAgo === 0 && $number % 3 === 0 ? 400.0 : 0.0;
                    $otherAmount = $monthsAgo === 0 && $number % 11 === 0 ? 300.0 : 0.0;
                    $previousBalanceAmount = $monthsAgo === 0 && $number % 6 === 0 ? 600.0 : 0.0;
                    $discountAmount = $monthsAgo === 0 && $number % 8 === 0 ? 250.0 : 0.0;
                    $totalAmount = $monthlyFeeAmount + $uniformAmount + $booksAmount
                        + $paperFundAmount + $otherAmount + $previousBalanceAmount - $discountAmount;
                    $amount = $totalAmount;
                    if ($monthsAgo === 0 && $number % 4 === 0) {
                        $amount = round($totalAmount * 0.6, 2);
                    }

                    $receiptNumber = 'DMO8'
                        .str_pad((string) $monthsAgo, 2, '0', STR_PAD_LEFT)
                        .str_pad((string) $number, 3, '0', STR_PAD_LEFT);

                    $payment = Payment::updateOrCreate(
                        ['receipt_number' => $receiptNumber],
                        [
                            'student_id' => $student->id,
                            'amount' => $amount,
                            'monthly_fee_amount' => $monthlyFeeAmount,
                            'uniform_amount' => $uniformAmount,
                            'books_amount' => $booksAmount,
                            'paper_fund_amount' => $paperFundAmount,
                            'other_amount' => $otherAmount,
                            'previous_balance_amount' => $previousBalanceAmount,
                            'discount_amount' => $discountAmount,
                            'total_amount' => $totalAmount,
                            'remaining_amount' => $totalAmount - $amount,
                            'description' => $monthsAgo === 0 && $amount < (float) $student->monthly_fee
                                ? 'Demo partial monthly fee payment'
                                : 'Demo monthly fee payment',
                            'payment_date' => $paymentDate->toDateString(),
                        ]
                    );

                    $payment->forceFill([
                        'created_at' => Carbon::parse($paymentDate)->setTime(10 + ($number % 6), 20),
                    ])->saveQuietly();
                }
            }
        });

        $demoStudentCount = Student::where('mobile', 'like', '0300990%')->count();
        $demoPaymentCount = Payment::where('receipt_number', 'like', 'DMO%')->count();

        $this->command?->info(
            "Demo data ready: {$demoStudentCount} students and {$demoPaymentCount} fee receipts."
        );
    }
}
