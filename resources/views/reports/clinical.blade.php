<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28mm 12mm 20mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1b1b18; }
        .header { position: fixed; top: -22mm; left: 0; right: 0; height: 20mm; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; height: 12mm; font-size: 8px; color: #5c5a55; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 12px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e3e3e0; padding: 4px 3px; text-align: left; vertical-align: top; }
        th { font-size: 9px; text-transform: uppercase; color: #5c5a55; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .flag { font-weight: bold; }
        .flag-H, .flag-L { color: #9a3412; }
        .flag-CH, .flag-CL { color: #991b1b; }
        .watermark { position: fixed; top: 95mm; left: 18mm; font-size: 42px; color: rgba(153, 27, 27, 0.12); transform: rotate(-28deg); }
        .meta td { border: 0; padding: 1px 6px 1px 0; }
        .compact h1 { font-size: 13px; }
        .center { text-align: center; }
        .muted { color: #5c5a55; }
    </style>
</head>
<body>
    @if(($report['status'] ?? '') !== 'released' && ($report['status'] ?? '') !== 'delivered')
        <div class="watermark">DRAFT</div>
    @endif
    @if(!empty($report['is_demo']))
        <div class="watermark" style="top: 120mm;">DEMONSTRATION</div>
    @endif

    <div class="header {{ $headerStyle === 'compact' ? 'compact' : '' }} {{ $headerStyle === 'centered' ? 'center' : '' }}">
        @if($headerStyle === 'centered')
            <h1>{{ $report['lab']['name'] }}</h1>
            <div class="muted">{{ $report['branch']['name'] }} · {{ $report['lab']['address'] }}</div>
        @elseif($headerStyle === 'compact')
            <strong>{{ $report['lab']['name'] }}</strong>
            <span class="muted"> · {{ $report['branch']['name'] }} · {{ $report['lab']['phone'] }} · {{ $report['lab']['licence_number'] }}</span>
        @else
            <table>
                <tr>
                    <td style="border:0; width: 70%;">
                        <h1>{{ $report['lab']['name'] }}</h1>
                        <div>{{ $report['lab']['legal_name'] }}</div>
                        <div class="muted">{{ $report['branch']['name'] }} — {{ $report['branch']['address'] }}</div>
                        <div class="muted">{{ $report['lab']['phone'] }} · {{ $report['lab']['email'] }} · {{ $report['lab']['licence_number'] }}</div>
                    </td>
                    <td style="border:0; text-align: right;">
                        <strong>Laboratory report</strong><br>
                        <span class="muted">{{ $report['template']['name'] }}</span>
                    </td>
                </tr>
            </table>
        @endif
    </div>

    <div class="footer">
        Reference intervals are method-specific starter values and must be verified by the laboratory director before clinical use.
        @if(!empty($report['is_demo'])) This document is demonstration data and is not a clinical report. @endif
        Verification does not display results.
    </div>

    <h2>Patient and order</h2>
    <table class="meta">
        <tr>
            <td><strong>Patient</strong><br>{{ $report['patient']['name'] }}</td>
            <td><strong>Patient ID</strong><br>{{ $report['patient']['number'] }}</td>
            <td><strong>Age / sex</strong><br>{{ $report['patient']['age'] ?? '—' }} / {{ $report['patient']['sex'] }}</td>
            <td><strong>Order</strong><br>{{ $report['order']['number'] }}</td>
        </tr>
        <tr>
            <td><strong>Referring doctor</strong><br>{{ $report['doctor']['name'] ?? '—' }}</td>
            <td><strong>Priority</strong><br>{{ $report['order']['priority'] }}</td>
            <td><strong>Collected</strong><br>{{ $report['specimens'][0]['collected_at'] ?? '—' }}</td>
            <td><strong>Reported</strong><br>{{ $report['generated_at'] }}</td>
        </tr>
    </table>

    <h2>Specimens</h2>
    <table>
        <thead>
            <tr><th>Accession / barcode</th><th>Specimen</th><th>Received</th><th>Condition</th></tr>
        </thead>
        <tbody>
            @foreach($report['specimens'] as $specimen)
                <tr>
                    <td>
                        {{ $specimen['barcode'] }}<br>
                        <img src="{{ $specimen['barcode_image'] }}" alt="Barcode" style="height: 36px;">
                    </td>
                    <td>{{ $specimen['type'] }}</td>
                    <td>{{ $specimen['received_at'] ?? '—' }}</td>
                    <td>{{ $specimen['condition'] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @foreach($report['sections'] as $section => $panels)
        <h2>{{ $section }}</h2>
        @foreach($panels as $panel)
            <p style="margin: 6px 0 2px;"><strong>{{ $panel['name'] }}</strong> <span class="muted">{{ $panel['specimen'] }} @if($panel['method']) · {{ $panel['method'] }} @endif</span></p>
            <table>
                <thead>
                    <tr>
                        <th>Test</th><th>Result</th><th>Unit</th><th>Reference interval</th><th>Flag</th><th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($panel['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $row['value'] }}</td>
                            <td>{{ $row['unit'] }}</td>
                            <td>{{ $row['interval'] }}</td>
                            <td class="flag flag-{{ $row['flag'] }}">{{ $row['flag'] }}</td>
                            <td>{{ $row['comment'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endforeach

    @if(!empty($report['correction_reason']))
        <p><strong>Correction:</strong> {{ $report['correction_reason'] }}</p>
    @endif

    <table style="margin-top: 16px;">
        <tr>
            <td style="border:0; width: 70%;">
                <strong>{{ $report['signatory']['name'] }}</strong><br>
                {{ $report['signatory']['qualification'] }}<br>
                {{ $report['signatory']['designation'] }}<br>
                <span class="muted">Signed {{ $report['signatory']['signed_at'] }}</span>
            </td>
            <td style="border:0; text-align: right;">
                <img src="{{ $report['qr'] }}" alt="Verification QR" style="height: 70px;"><br>
                <span class="muted">Scan to verify authenticity</span>
            </td>
        </tr>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('DejaVu Sans');
            $pdf->page_text(460, 810, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.35, 0.35, 0.35]);
        }
    </script>
</body>
</html>
