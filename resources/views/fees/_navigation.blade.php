@php
    $feeNavMonth = $month ?? $selectedMonth ?? null;
    $feeNavMonth = $feeNavMonth instanceof \Carbon\CarbonInterface ? $feeNavMonth->format('Y-m') : ($feeNavMonth ?: request('month', today()->format('Y-m')));
@endphp
<nav class="fee-module-navigation no-print" aria-label="Fees module sections">
    <a class="{{ request()->routeIs('family-fees.*', 'fees.families.ledger') ? 'active' : '' }}" href="{{ route('family-fees.index', ['month' => $feeNavMonth]) }}"><i class="fa-solid fa-people-roof"></i><span><strong>Family Fees</strong><small>Families, slips and collection</small></span></a>
    <a class="{{ request()->routeIs('fees.index', 'fees.show', 'fees.invoices.*', 'fees.students.ledger') ? 'active' : '' }}" href="{{ route('fees.index', ['month' => $feeNavMonth]) }}"><i class="fa-solid fa-user-graduate"></i><span><strong>Individual Student Fees</strong><small>Monthly invoices and charges</small></span></a>
    <a class="{{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="fa-solid fa-book-open"></i><span><strong>Payment History / Ledgers</strong><small>Receipts and transaction history</small></span></a>
    @if((Auth::user()->role ?: 'admin') === 'admin')
    <a class="{{ request()->routeIs('fees.settings', 'sibling-discounts.*') ? 'active' : '' }}" href="{{ route('fees.settings') }}"><i class="fa-solid fa-sliders"></i><span><strong>Fee Settings</strong><small>Structures, categories and rules</small></span></a>
    @endif
</nav>
