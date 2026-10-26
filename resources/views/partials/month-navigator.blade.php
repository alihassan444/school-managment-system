@php
    $routeParams = $routeParams ?? [];
@endphp
<nav class="month-navigator" aria-label="Billing month navigation">
    <a class="btn btn-outline-primary" href="{{ route($routeName, array_merge($routeParams, ['month' => $month->copy()->subMonth()->format('Y-m')])) }}"><i class="fa-solid fa-chevron-left"></i><span>Previous</span></a>
    <form method="GET" action="{{ route($routeName, $routeParams) }}" class="month-picker-form">
        @foreach($routeParams as $name => $value)
            @if($value !== null && $value !== '')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
        @endforeach
        <label for="{{ $monthInputId ?? 'billingMonth' }}" class="visually-hidden">Billing month</label>
        <input id="{{ $monthInputId ?? 'billingMonth' }}" class="form-control" type="month" name="month" value="{{ $month->format('Y-m') }}" aria-label="Selected billing month">
        <button class="btn btn-primary" type="submit">View</button>
    </form>
    <a class="btn btn-outline-primary" href="{{ route($routeName, array_merge($routeParams, ['month' => $month->copy()->addMonth()->format('Y-m')])) }}"><span>Next</span><i class="fa-solid fa-chevron-right"></i></a>
    <a class="btn btn-outline-secondary" href="{{ route($routeName, array_merge($routeParams, ['month' => today()->format('Y-m')])) }}">Current month</a>
</nav>
