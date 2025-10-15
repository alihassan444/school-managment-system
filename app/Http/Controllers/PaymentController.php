<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with('student');

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%");
            })->orWhere('receipt_number', 'like', "%{$search}%");
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate(15);

        return view('payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $student = null;
        if ($request->has('student_id')) {
            $student = Student::findOrFail($request->student_id);
        }

        $students = Student::active()->orderBy('name')->get();
        
        return view('payments.create', compact('student', 'students'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|exists:students,id',
                'amount' => 'required|numeric|min:0.01|max:999999.99',
                'description' => 'nullable|string|max:1000',
                'payment_date' => 'required|date|before_or_equal:today'
            ]);

            $payment = DB::transaction(function () use ($validated) {
                $student = Student::findOrFail($validated['student_id']);

                return Payment::create([
                    'student_id' => $validated['student_id'],
                    'amount' => $validated['amount'],
                    'description' => $validated['description'] ?? null,
                    'payment_date' => $validated['payment_date'],
                    'receipt_number' => Payment::generateReceiptNumber()
                ]);
            });

            return redirect()->route('payments.show', $payment)
                            ->with('success', "Payment recorded successfully! Receipt: {$payment->receipt_number}");
        } catch (\Exception $e) {
            Log::error('Error recording payment: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource (Receipt).
     */
    public function show(Payment $payment)
    {
        return view('payments.receipt', compact('payment'));
    }

    /**
     * Print fee bill for a student
     */
    public function printFeeBill(Student $student)
    {
        return view('payments.fee-bill', compact('student'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        try {
            $receiptNumber = $payment->receipt_number;
            $payment->delete();
            return redirect()->route('payments.index')
                            ->with('success', "Payment (Receipt: {$receiptNumber}) deleted successfully!");
        } catch (\Exception $e) {
            return redirect()->back()
                            ->with('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }
    // Export and advanced filter helpers removed in simplified billing system
}
