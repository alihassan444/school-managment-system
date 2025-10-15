<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');

// Student routes - protected by auth middleware
Route::middleware('auth')->group(function () {
    Route::resource('students', App\Http\Controllers\StudentController::class);
    
    // Payment routes
    Route::resource('payments', App\Http\Controllers\PaymentController::class)->except(['edit', 'update']);
    Route::get('/students/{student}/fee-bill', [App\Http\Controllers\PaymentController::class, 'printFeeBill'])->name('payments.fee-bill');
    
    // Export routes
    Route::get('/payments/export/excel', [App\Http\Controllers\PaymentController::class, 'exportExcel'])->name('payments.export.excel');
    Route::get('/payments/export/pdf', [App\Http\Controllers\PaymentController::class, 'exportPDF'])->name('payments.export.pdf');
});
