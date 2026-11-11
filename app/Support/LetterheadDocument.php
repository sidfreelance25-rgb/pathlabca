<?php

namespace App\Support;

use App\Services\BarcodeService;

class LetterheadDocument
{
    public function __construct(private BarcodeService $barcodes) {}

    /**
     * @param  array<string, mixed>  $design
     * @param  array<string, mixed>  $report
     */
    public function html(array $design, array $report): string
    {
        $theme = in_array($design['theme'] ?? '', ['t1', 't2', 't3', 't4', 't5', 't6'], true) ? $design['theme'] : 't1';
        $sampleId = (string) ($report['sample_id'] ?? 'SAMPLE');
        $qrText = (string) ($report['qr'] ?? $sampleId);

        return view('reports.letterhead', [
            'design' => $design,
            'theme' => $theme,
            't' => Letterhead::texts($design),
            'logo' => is_string($design['logo'] ?? null) ? $design['logo'] : '',
            'sign' => is_string($design['sign'] ?? null) ? $design['sign'] : '',
            'barcode' => $this->barcodes->code128DataUri($sampleId),
            'qr' => $this->barcodes->qrDataUri($qrText),
            'sampleId' => $sampleId,
            'title' => (string) ($report['title'] ?? 'Laboratory report'),
            'patientName' => (string) ($report['patient'] ?? ''),
            'ageSex' => (string) ($report['agesex'] ?? ''),
            'referredBy' => (string) ($report['refBy'] ?? ''),
            'reportDate' => (string) ($report['repDate'] ?? ''),
            'collected' => (string) ($report['collected'] ?? ''),
            'rows' => is_array($report['rows'] ?? null) ? $report['rows'] : [],
            'note' => (string) ($report['note'] ?? ''),
            'draft' => (bool) ($report['draft'] ?? false),
            'demo' => (bool) ($report['demo'] ?? false),
        ])->render();
    }

    public function pdf(string $html): ?string
    {
        $directory = storage_path('app/private/tmp');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $htmlPath = $directory.'/'.uniqid('letterhead-', true).'.html';
        $pdfPath = $directory.'/'.uniqid('letterhead-', true).'.pdf';
        $profile = $directory.'/chrome-'.uniqid('', true);
        mkdir($profile);
        file_put_contents($htmlPath, $html);
        $command = 'google-chrome --headless --disable-gpu --no-sandbox --user-data-dir='.escapeshellarg($profile).' --no-pdf-header-footer --virtual-time-budget=8000 --print-to-pdf='.escapeshellarg($pdfPath).' '.escapeshellarg($htmlPath);
        exec($command, $output, $code);
        $binary = ($code === 0 && is_file($pdfPath)) ? file_get_contents($pdfPath) : false;
        @unlink($htmlPath);
        @unlink($pdfPath);
        exec('rm -rf '.escapeshellarg($profile));

        return is_string($binary) && strlen($binary) > 1000 ? $binary : null;
    }
}
