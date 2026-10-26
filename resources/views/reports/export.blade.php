<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }} · {{ $schoolName }}</title>
    <style>
        @page { size: A4 {{ $report['orientation'] }}; margin: 13mm 10mm 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #101b17; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; background: #fff; }
        .print-toolbar { display: flex; justify-content: flex-end; gap: 8px; margin: 12px auto; max-width: 1120px; }
        .print-toolbar button { padding: 9px 16px; border: 0; border-radius: 6px; color: #fff; background: #12614e; cursor: pointer; }
        .sheet { max-width: 1120px; margin: 0 auto; }
        header { display: table; width: 100%; border-bottom: 3px solid #12614e; padding-bottom: 10px; margin-bottom: 14px; }
        .logo, .heading { display: table-cell; vertical-align: middle; }
        .logo { width: 68px; }
        .logo img { width: 58px; height: 58px; object-fit: contain; }
        h1 { margin: 0 0 3px; color: #12614e; font-size: 21px; letter-spacing: .02em; }
        h2 { margin: 0; font-size: 14px; text-transform: uppercase; }
        .period { color: #53655d; margin-top: 4px; }
        .meta { width: 100%; border-collapse: collapse; margin: 0 0 12px; }
        .meta td { border: 1px solid #d7e0dc; padding: 7px; }
        .meta small { display: block; color: #687870; text-transform: uppercase; font-size: 7px; }
        .stats { width: 100%; border-collapse: separate; border-spacing: 5px; margin: 0 0 12px; }
        .stats td { border: 1px solid #cfdad5; padding: 8px; text-align: center; }
        .stats strong { display: block; color: #12614e; font-size: 13px; }
        table.data { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; page-break-after: auto; }
        table.data th { padding: 7px 5px; color: #fff; background: #12614e; border: 1px solid #0d4e3e; text-align: left; font-size: 8px; text-transform: uppercase; }
        table.data td { padding: 6px 5px; border: 1px solid #cad5d0; vertical-align: top; }
        .status { text-transform: capitalize; font-weight: bold; }
        .notes { margin-top: 12px; color: #53655d; }
        .notes p { margin: 3px 0; }
        .signatures { display: table; width: 100%; margin-top: 42px; page-break-inside: avoid; }
        .signature { display: table-cell; width: 50%; text-align: center; padding: 0 35px; }
        .signature span { display: block; border-top: 1px solid #222; padding-top: 5px; }
        footer { position: fixed; left: 0; right: 0; bottom: -10mm; color: #718078; font-size: 8px; }
        @media print { .print-toolbar { display: none; } .sheet { max-width: none; } }
    </style>
</head>
<body>
@if($autoPrint ?? false)<div class="print-toolbar"><button type="button" onclick="window.print()">Print report</button></div>@endif
<main class="sheet">
    <header>
        <div class="logo">@if($logoDataUri)<img src="{{ $logoDataUri }}" alt="School logo">@endif</div>
        <div class="heading"><h1>{{ $schoolName }}</h1><h2>{{ $report['title'] }}</h2><div class="period">{{ $report['period']['label'] }}</div></div>
    </header>

    @if($report['context'])
        <table class="meta"><tr>@foreach($report['context'] as $label => $value)<td><small>{{ $label }}</small><strong>{{ $value ?: '—' }}</strong></td>@endforeach</tr></table>
    @endif
    @if($report['stats'])
        <table class="stats"><tr>@foreach($report['stats'] as $stat)<td><strong>{{ $stat['value'] }}</strong>{{ $stat['label'] }}</td>@endforeach</tr></table>
    @endif

    <table class="data">
        <thead><tr>@foreach($report['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($report['rows'] as $row)
            <tr>@foreach($report['columns'] as $column)
                @php($value = data_get($row, $column['key']))
                <td class="{{ ($column['type'] ?? null) === 'status' ? 'status' : '' }}">
                    @if(($column['type'] ?? null) === 'money')Rs {{ number_format((float) $value) }}
                    @elseif(($column['type'] ?? null) === 'percentage'){{ $value === null ? '—' : number_format((float) $value, 1).'%' }}
                    @else{{ $value === null || $value === '' ? '—' : $value }}@endif
                </td>
            @endforeach</tr>
        @empty
            <tr><td colspan="{{ max(count($report['columns']), 1) }}">No records match the selected filters.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($report['notes'])<div class="notes">@foreach($report['notes'] as $note)<p>• {{ $note }}</p>@endforeach</div>@endif
    @if($report['signatures'])<div class="signatures"><div class="signature"><span>Class Teacher Signature</span></div><div class="signature"><span>Principal Signature</span></div></div>@endif
</main>
<footer>Generated {{ $generatedAt->format('d M Y, h:i A') }} · Computer-generated school report</footer>
@if($autoPrint ?? false)<script>window.addEventListener('load', () => window.print());</script>@endif
</body>
</html>
