<div class="rep">
    <h2>{{ $title }}</h2>
    <div class="pt">
        <div><b>Patient:</b> {{ $patientName }}</div>
        <div><b>Report date:</b> {{ $reportDate }}</div>
        <div><b>Age / Sex:</b> {{ $ageSex }}</div>
        <div><b>Sample ID:</b> {{ $sampleId }}</div>
        <div><b>Referred by:</b> {{ $referredBy }}</div>
        <div><b>Collected:</b> {{ $collected }}</div>
        <div class="qrbox"><img src="{{ $qr }}" alt="QR" style="width:20mm;height:20mm"></div>
    </div>
    @if($draft)
        <p style="color:#b3261e;font-weight:700">DRAFT — not for release</p>
    @endif
    @if($demo)
        <p style="color:#b3261e;font-weight:700">DEMONSTRATION — not a clinical report</p>
    @endif
    <table>
        <tr><th>Test</th><th>Result</th><th>Unit</th><th>Reference range</th><th>Flag</th></tr>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="{{ in_array($row['flag'], ['H','L','CH','CL'], true) ? 'hi' : '' }}">{{ $row['value'] }}</td>
                <td>{{ $row['unit'] }}</td>
                <td>{{ $row['interval'] }}</td>
                <td>{{ $row['flag'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No results</td></tr>
        @endforelse
    </table>
    @if($note !== '')
        <p>{{ $note }}</p>
    @endif
</div>
