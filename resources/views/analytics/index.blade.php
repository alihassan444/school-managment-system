@extends('layouts.dashboard')

@section('title', 'Analytics')
@section('eyebrow', 'School insights')

@section('content')
<div class="page-header">
    <div class="d-flex justify-content-between align-items-end gap-3">
        <div>
            <span class="page-kicker">Live reporting</span>
            <h1 class="page-title">See the school at a glance.</h1>
            <p class="page-subtitle">Student growth, fee collections, and enrollment health—calculated directly from your records.</p>
        </div>
        <form method="GET" action="{{ route('analytics') }}" class="analytics-date-form">
            <label for="analyticsDate"><i class="fa-regular fa-calendar"></i><span>Record date</span></label>
            <input type="date" id="analyticsDate" name="date" value="{{ $selectedDate->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="Analytics record date">
            <button type="submit" class="btn btn-primary btn-sm">View</button>
            @unless($isToday)
                <a href="{{ route('analytics') }}" class="btn btn-outline-secondary btn-sm">Today</a>
            @endunless
        </form>
    </div>
</div>

<section class="stats-grid" aria-label="Analytics summary">
    <article class="stat-card" style="--stat-color:#0e8f79;--stat-tint:#dff5ee">
        <div class="stat-top"><span class="stat-label">Total students</span><span class="stat-icon"><i class="fa-solid fa-user-graduate"></i></span></div>
        <div class="stat-value" data-count="{{ $totalStudents }}">0</div>
        <span class="stat-label">Added by {{ $selectedDate->format('d M Y') }}</span>
    </article>
    <article class="stat-card" style="--stat-color:#2576c4;--stat-tint:#e3f0fc">
        <div class="stat-top"><span class="stat-label">Active rate</span><span class="stat-icon"><i class="fa-solid fa-chart-line"></i></span></div>
        <div class="stat-value" data-count="{{ $activeRate }}" data-suffix="%">0%</div>
        <span class="stat-label">Currently active students</span>
    </article>
    <article class="stat-card" style="--stat-color:#b77a00;--stat-tint:#fff2d4">
        <div class="stat-top"><span class="stat-label">Fees collected</span><span class="stat-icon"><i class="fa-solid fa-wallet"></i></span></div>
        <div class="stat-value" data-count="{{ $totalFees }}" data-prefix="Rs ">Rs 0</div>
        <span class="stat-label">{{ number_format($paymentCount) }} {{ Str::plural('receipt', $paymentCount) }} by this date</span>
    </article>
    <article class="stat-card" style="--stat-color:#d15447;--stat-tint:#ffebe7">
        <div class="stat-top"><span class="stat-label">Average receipt</span><span class="stat-icon"><i class="fa-solid fa-receipt"></i></span></div>
        <div class="stat-value" data-count="{{ $averagePayment }}" data-prefix="Rs ">Rs 0</div>
        <span class="stat-label">Per recorded payment</span>
    </article>
</section>

<section class="dashboard-card daily-record-card" aria-labelledby="daily-record-title">
    <div class="card-header">
        <div>
            <h2 class="card-title" id="daily-record-title"><i class="fa-solid fa-calendar-day me-2"></i>Daily record</h2>
            <small class="text-muted">{{ $selectedDate->format('l, d F Y') }}</small>
        </div>
        <span class="daily-status-badge"><i class="fa-solid fa-rotate"></i>{{ $isToday ? 'Updated today' : 'Historical view' }}</span>
    </div>
    <div class="card-body">
        <div class="daily-summary-grid">
            <div><span>Fees received</span><strong>Rs {{ number_format($dailyFees, 0) }}</strong></div>
            <div><span>Receipts issued</span><strong>{{ number_format($dailyPayments->count()) }}</strong></div>
            <div><span>Students added</span><strong>{{ number_format($dailyStudents->count()) }}</strong></div>
        </div>

        <div class="daily-record-grid">
            <article class="daily-record-column">
                <div class="daily-record-column-title"><h3>Fee payments</h3><span>{{ $dailyPayments->count() }}</span></div>
                <div class="activity-list">
                    @forelse($dailyPayments as $payment)
                        <a href="{{ route('payments.show', $payment) }}" class="activity-row text-decoration-none">
                            <span class="activity-icon"><i class="fa-solid fa-receipt"></i></span>
                            <span class="activity-main"><strong>{{ $payment->student->name }}</strong><small>{{ $payment->receipt_number }} · {{ $payment->student->class }}</small></span>
                            <span class="activity-amount">Rs {{ number_format($payment->amount, 0) }}</span>
                        </a>
                    @empty
                        <div class="daily-empty"><i class="fa-regular fa-folder-open"></i><span>No fee payments on this date.</span></div>
                    @endforelse
                </div>
            </article>

            <article class="daily-record-column">
                <div class="daily-record-column-title"><h3>New students</h3><span>{{ $dailyStudents->count() }}</span></div>
                <div class="activity-list">
                    @forelse($dailyStudents as $student)
                        <a href="{{ route('students.show', $student) }}" class="activity-row text-decoration-none">
                            <span class="activity-icon activity-icon-gold"><i class="fa-solid fa-user-plus"></i></span>
                            <span class="activity-main"><strong>{{ $student->name }}</strong><small>{{ $student->class }} · {{ ucfirst(str_replace('_', ' ', $student->campus)) }}</small></span>
                            <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($student->status) }}</span>
                        </a>
                    @empty
                        <div class="daily-empty"><i class="fa-regular fa-folder-open"></i><span>No students were added on this date.</span></div>
                    @endforelse
                </div>
            </article>
        </div>
    </div>
</section>

<section class="fee-position-card" aria-labelledby="fee-position-title">
    <div class="fee-position-heading">
        <div>
            <span class="page-kicker">{{ $selectedDate->format('F Y') }} · through {{ $selectedDate->format('d M') }}</span>
            <h2 id="fee-position-title">Monthly fee position</h2>
            <p>Billing-period charges and payments applied to those invoices are kept separate from cash receipt dates.</p>
        </div>
        <div class="fee-position-rate" aria-label="{{ $collectionRate }} percent collected">
            <strong>{{ $collectionRate }}%</strong>
            <span>collected</span>
        </div>
    </div>

    <div class="fee-position-metrics">
        <div class="fee-position-metric">
            <span><i class="fa-solid fa-bullseye"></i>Expected</span>
            <strong>Rs {{ number_format($expectedThisMonth, 0) }}</strong>
            <small>{{ number_format($studentStatus['active']) }} active {{ Str::plural('student', $studentStatus['active']) }}</small>
        </div>
        <div class="fee-position-metric fee-position-collected">
            <span><i class="fa-solid fa-circle-check"></i>Collected</span>
            <strong>Rs {{ number_format($collectedThisMonth, 0) }}</strong>
            <small>Applied to selected-month invoices through this date</small>
        </div>
        <div class="fee-position-metric fee-position-remaining">
            <span><i class="fa-solid fa-hourglass-half"></i>Fee left</span>
            <strong>Rs {{ number_format($remainingThisMonth, 0) }}</strong>
            <small>{{ $overpaidThisMonth > 0 ? 'Rs '.number_format($overpaidThisMonth, 0).' received above expected' : 'Outstanding as of this date' }}</small>
        </div>
    </div>

    <div class="fee-progress" role="progressbar" aria-valuenow="{{ $collectionRate }}" aria-valuemin="0" aria-valuemax="100" aria-label="Monthly fee collection progress">
        <span style="--fee-progress: {{ $collectionRate }}%"></span>
    </div>

    @if($studentsWithoutFee > 0)
        <div class="fee-setup-warning">
            <i class="fa-solid fa-circle-info"></i>
            <span><strong>{{ $studentsWithoutFee }} active {{ Str::plural('student', $studentsWithoutFee) }}</strong> {{ $studentsWithoutFee === 1 ? 'has' : 'have' }} no monthly fee set, so {{ $studentsWithoutFee === 1 ? 'that student is' : 'those students are' }} not included in the expected total.</span>
            <a href="{{ route('students.index', ['status' => 'active']) }}">Update students</a>
        </div>
    @endif
</section>

<section class="analytics-grid analytics-grid-wide">
    <article class="dashboard-card chart-card">
        <div class="card-header">
            <div><h2 class="card-title"><i class="fa-solid fa-chart-column me-2"></i>Fee collection trend</h2><small class="text-muted">Six months ending {{ $selectedDate->format('d M Y') }}</small></div>
            <span class="chart-legend"><i style="background:#0e8f79"></i>Collected</span>
        </div>
        <div class="card-body"><div class="chart-wrap"><canvas id="feesChart" role="img" aria-label="Monthly fee collection bar chart"></canvas></div></div>
    </article>

    <article class="dashboard-card analytics-insight-card">
        <div class="card-header"><h2 class="card-title"><i class="fa-solid fa-lightbulb me-2"></i>Collection insight</h2></div>
        <div class="card-body">
            <span class="insight-label">Strongest month</span>
            <strong class="insight-value">{{ ($bestMonth['fees'] ?? 0) > 0 ? $bestMonth['full_label'] : 'No data yet' }}</strong>
            <span class="insight-amount">Rs {{ number_format($bestMonth['fees'] ?? 0, 0) }}</span>
            <div class="insight-divider"></div>
            <p>{{ $paymentCount > 0 ? 'Your fee chart updates automatically whenever a new receipt is recorded.' : 'Record the first fee payment to begin building the collection trend.' }}</p>
            <a href="{{ route('payments.create') }}" class="btn btn-primary w-100"><i class="fa-solid fa-plus me-2"></i>Record fee</a>
        </div>
    </article>
</section>

<section class="analytics-grid">
    <article class="dashboard-card chart-card">
        <div class="card-header">
            <div><h2 class="card-title"><i class="fa-solid fa-arrow-trend-up me-2"></i>New student trend</h2><small class="text-muted">Records added each month</small></div>
            <span class="chart-legend"><i style="background:#f4b740"></i>Students</span>
        </div>
        <div class="card-body"><div class="chart-wrap chart-wrap-compact"><canvas id="studentsChart" role="img" aria-label="Monthly new student line chart"></canvas></div></div>
    </article>

    <article class="dashboard-card chart-card">
        <div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-circle-half-stroke me-2"></i>Enrollment status</h2><small class="text-muted">Active and inactive records</small></div></div>
        <div class="card-body status-chart-layout">
            <div class="donut-wrap"><canvas id="statusChart" role="img" aria-label="Student status doughnut chart"></canvas></div>
            <div class="status-legend">
                <div><span class="legend-dot" style="background:#0e8f79"></span><span><small>Active</small><strong>{{ number_format($studentStatus['active']) }}</strong></span></div>
                <div><span class="legend-dot" style="background:#d9e1dd"></span><span><small>Inactive</small><strong>{{ number_format($studentStatus['inactive']) }}</strong></span></div>
            </div>
        </div>
    </article>
</section>

<section class="analytics-grid">
    <article class="dashboard-card">
        <div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-school-flag me-2"></i>Campus distribution</h2><small class="text-muted">Where students are enrolled</small></div></div>
        <div class="card-body distribution-list">
            @php($campusMax = max(max($campuses), 1))
            @foreach($campuses as $campus => $count)
                <div class="distribution-row">
                    <div class="distribution-copy"><strong>{{ $campus }}</strong><span>{{ $count }} {{ Str::plural('student', $count) }}</span></div>
                    <div class="distribution-track"><span style="--bar-width:{{ ($count / $campusMax) * 100 }}%"></span></div>
                </div>
            @endforeach
        </div>
    </article>

    <article class="dashboard-card">
        <div class="card-header"><div><h2 class="card-title"><i class="fa-solid fa-layer-group me-2"></i>Top classes</h2><small class="text-muted">Largest classes by record count</small></div></div>
        <div class="card-body distribution-list">
            @php($classMax = max((int) ($classes->max('total') ?? 0), 1))
            @forelse($classes as $class)
                <div class="distribution-row">
                    <div class="distribution-copy"><strong>{{ $class->class }}</strong><span>{{ $class->total }} {{ Str::plural('student', $class->total) }}</span></div>
                    <div class="distribution-track distribution-track-gold"><span style="--bar-width:{{ ($class->total / $classMax) * 100 }}%"></span></div>
                </div>
            @empty
                <div class="empty-state py-3"><span class="empty-state-icon"><i class="fa-solid fa-layer-group"></i></span><h5>No class data yet</h5><p>Class distribution appears after students are added.</p></div>
            @endforelse
        </div>
    </article>
</section>

<table class="visually-hidden">
    <caption>Six month school analytics</caption>
    <thead><tr><th>Month</th><th>Fees</th><th>Payments</th><th>New students</th></tr></thead>
    <tbody>@foreach($monthly as $month)<tr><td>{{ $month['full_label'] }}</td><td>{{ $month['fees'] }}</td><td>{{ $month['payments'] }}</td><td>{{ $month['students'] }}</td></tr>@endforeach</tbody>
</table>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const analytics = {
        labels: @json($monthly->pluck('label')->values()),
        fees: @json($monthly->pluck('fees')->values()),
        students: @json($monthly->pluck('students')->values()),
        status: [{{ $studentStatus['active'] }}, {{ $studentStatus['inactive'] }}],
    };

    const colors = { ink: '#0b1f33', muted: '#73827e', line: '#e5ebe8', emerald: '#0e8f79', mint: '#dff5ee', gold: '#f4b740', inactive: '#d9e1dd' };
    const money = new Intl.NumberFormat('en-PK', { notation: 'compact', maximumFractionDigits: 1 });

    const prepare = (id, height) => {
        const canvas = document.getElementById(id);
        const width = canvas.parentElement.clientWidth;
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        const context = canvas.getContext('2d');
        context.scale(ratio, ratio);
        context.font = '12px DM Sans';
        return { canvas, context, width, height };
    };

    const roundedRect = (ctx, x, y, width, height, radius) => {
        const r = Math.min(radius, width / 2, height / 2);
        ctx.beginPath();
        ctx.roundRect(x, y, width, height, r);
    };

    const drawFees = (progress = 1) => {
        const { context: ctx, width, height } = prepare('feesChart', 280);
        const pad = { left: 54, right: 12, top: 18, bottom: 34 };
        const plotWidth = width - pad.left - pad.right;
        const plotHeight = height - pad.top - pad.bottom;
        const hasData = analytics.fees.some((value) => value > 0);
        const max = Math.max(...analytics.fees, 1);

        ctx.clearRect(0, 0, width, height);
        for (let i = 0; i <= 4; i += 1) {
            const y = pad.top + (plotHeight / 4) * i;
            ctx.strokeStyle = colors.line;
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke();
            ctx.fillStyle = colors.muted;
            ctx.textAlign = 'right';
            ctx.fillText(hasData ? `Rs ${money.format(max * (1 - i / 4))}` : (i === 4 ? 'Rs 0' : '—'), pad.left - 9, y + 4);
        }

        const slot = plotWidth / analytics.labels.length;
        const barWidth = Math.min(slot * .46, 46);
        analytics.fees.forEach((value, index) => {
            const barHeight = (value / max) * plotHeight * progress;
            const x = pad.left + slot * index + (slot - barWidth) / 2;
            const y = pad.top + plotHeight - barHeight;
            const gradient = ctx.createLinearGradient(0, y, 0, pad.top + plotHeight);
            gradient.addColorStop(0, '#19aa90'); gradient.addColorStop(1, '#08705f');
            ctx.fillStyle = gradient;
            roundedRect(ctx, x, y, barWidth, Math.max(barHeight, value > 0 ? 4 : 0), 9);
            ctx.fill();
            ctx.fillStyle = colors.muted;
            ctx.textAlign = 'center';
            ctx.fillText(analytics.labels[index], x + barWidth / 2, height - 10);
        });

        if (!hasData) {
            ctx.fillStyle = colors.ink;
            ctx.font = '700 14px Manrope';
            ctx.textAlign = 'center';
            ctx.fillText('No fee data to chart yet', pad.left + plotWidth / 2, pad.top + plotHeight / 2 - 4);
            ctx.fillStyle = colors.muted;
            ctx.font = '12px DM Sans';
            ctx.fillText('New receipts will appear here automatically.', pad.left + plotWidth / 2, pad.top + plotHeight / 2 + 18);
        }
    };

    const drawStudents = (progress = 1) => {
        const { context: ctx, width, height } = prepare('studentsChart', 250);
        const pad = { left: 34, right: 18, top: 20, bottom: 34 };
        const plotWidth = width - pad.left - pad.right;
        const plotHeight = height - pad.top - pad.bottom;
        const max = Math.max(...analytics.students, 1);
        ctx.clearRect(0, 0, width, height);

        for (let i = 0; i <= 3; i += 1) {
            const y = pad.top + (plotHeight / 3) * i;
            ctx.strokeStyle = colors.line; ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke();
        }

        const points = analytics.students.map((value, index) => ({
            x: pad.left + (plotWidth / Math.max(analytics.students.length - 1, 1)) * index,
            y: pad.top + plotHeight - ((value / max) * plotHeight * progress),
        }));
        const baseline = pad.top + plotHeight;
        const fill = ctx.createLinearGradient(0, pad.top, 0, baseline);
        fill.addColorStop(0, 'rgba(244,183,64,.28)'); fill.addColorStop(1, 'rgba(244,183,64,0)');
        ctx.beginPath(); ctx.moveTo(points[0].x, baseline); points.forEach((point) => ctx.lineTo(point.x, point.y)); ctx.lineTo(points.at(-1).x, baseline); ctx.closePath(); ctx.fillStyle = fill; ctx.fill();
        ctx.beginPath(); points.forEach((point, index) => index ? ctx.lineTo(point.x, point.y) : ctx.moveTo(point.x, point.y)); ctx.strokeStyle = colors.gold; ctx.lineWidth = 3; ctx.lineJoin = 'round'; ctx.stroke();
        points.forEach((point, index) => {
            ctx.beginPath(); ctx.arc(point.x, point.y, 5, 0, Math.PI * 2); ctx.fillStyle = '#fff'; ctx.fill(); ctx.lineWidth = 3; ctx.strokeStyle = colors.gold; ctx.stroke();
            ctx.fillStyle = colors.muted; ctx.textAlign = 'center'; ctx.fillText(analytics.labels[index], point.x, height - 10);
        });
    };

    const drawStatus = (progress = 1) => {
        const { context: ctx, width, height } = prepare('statusChart', 205);
        const total = analytics.status.reduce((sum, value) => sum + value, 0);
        const cx = width / 2; const cy = height / 2; const radius = Math.min(width, height) * .36;
        ctx.clearRect(0, 0, width, height);
        ctx.lineWidth = 22; ctx.lineCap = 'round';
        ctx.strokeStyle = colors.inactive; ctx.beginPath(); ctx.arc(cx, cy, radius, 0, Math.PI * 2); ctx.stroke();
        if (total > 0) {
            const activeAngle = (analytics.status[0] / total) * Math.PI * 2 * progress;
            ctx.strokeStyle = colors.emerald; ctx.beginPath(); ctx.arc(cx, cy, radius, -Math.PI / 2, -Math.PI / 2 + activeAngle); ctx.stroke();
        }
        ctx.fillStyle = colors.ink; ctx.font = '800 28px Manrope'; ctx.textAlign = 'center'; ctx.fillText(`${total ? Math.round(analytics.status[0] / total * 100) : 0}%`, cx, cy + 3);
        ctx.fillStyle = colors.muted; ctx.font = '11px DM Sans'; ctx.fillText('ACTIVE', cx, cy + 23);
    };

    const drawAll = (progress) => { drawFees(progress); drawStudents(progress); drawStatus(progress); };
    const started = performance.now();
    const animate = (now) => {
        const progress = Math.min((now - started) / 900, 1);
        const eased = 1 - Math.pow(1 - progress, 4);
        drawAll(eased);
        if (progress < 1) requestAnimationFrame(animate);
    };
    requestAnimationFrame(animate);

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => drawAll(1), 120);
    });
});
</script>
@endpush
