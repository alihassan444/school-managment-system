<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\SchoolSetting;
use App\Services\Reports\SchoolReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function __construct(private readonly SchoolReportService $reports) {}

    public function index(ReportFilterRequest $request)
    {
        $report = $this->reports->generate($request);
        $options = $this->reports->filterOptions($request);

        return view('reports.index', array_merge($options, $report['legacy'] ?? [], [
            'report' => $report,
            'reportTypes' => $this->reports->reportTypes($request->user()),
            'filterMap' => SchoolReportService::FILTERS,
            'selected' => $request->validated(),
            'schoolName' => SchoolSetting::value('school_name', config('app.name', 'Al Suffaa School')),
        ]));
    }

    public function pdf(ReportFilterRequest $request)
    {
        $data = $this->exportData($request);
        $pdf = Pdf::loadView('reports.export', $data)->setPaper('a4', $data['report']['orientation']);
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text($canvas->get_width() - 92, $canvas->get_height() - 18, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.35, 0.42, 0.39]);

        return $pdf->download($this->filename($data['report'], 'pdf'));
    }

    public function print(ReportFilterRequest $request)
    {
        return view('reports.export', array_merge($this->exportData($request), ['autoPrint' => true]));
    }

    public function csv(ReportFilterRequest $request)
    {
        $report = $this->reports->generate($request);

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, collect($report['columns'])->pluck('label')->all());
            foreach ($report['rows'] as $row) {
                fputcsv($output, collect($report['columns'])->map(fn ($column) => data_get($row, $column['key']))->all());
            }
            fclose($output);
        }, $this->filename($report, 'csv'), ['Content-Type' => 'text/csv']);
    }

    public function students(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'academic_session_id' => ['nullable', 'integer', 'exists:academic_sessions,id'],
        ]);

        return response()->json($this->reports->studentOptions($request)->map(fn ($student) => [
            'id' => $student->id,
            'name' => $student->name,
            'roll_no' => $student->roll_no,
        ])->values());
    }

    private function exportData(ReportFilterRequest $request): array
    {
        $logoPath = public_path('images/al-suffaa-school-crest-print.jpg');

        return [
            'report' => $this->reports->generate($request),
            'schoolName' => SchoolSetting::value('school_name', config('app.name', 'Al Suffaa School')),
            'logoDataUri' => is_file($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null,
            'generatedAt' => now(),
            'autoPrint' => false,
        ];
    }

    private function filename(array $report, string $extension): string
    {
        return Str::slug($report['title']).'-'.now()->format('Ymd-His').'.'.$extension;
    }
}
