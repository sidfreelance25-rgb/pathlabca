@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="row" style="justify-content:space-between;margin-bottom:1rem;">
        <h1 style="margin:0;">Reports</h1>
        <a class="btn" href="{{ route('reports.create') }}">New report</a>
    </div>

    <div class="card">
        @if ($reports->isEmpty())
            <p>No reports yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Refer by</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td><a href="{{ route('reports.show', $report) }}">{{ $report->patient_name }}</a></td>
                            <td>{{ $report->referred_by_name }}</td>
                            <td>{{ ucfirst($report->status) }}</td>
                            <td>{{ $report->created_at->format('d M Y H:i') }}</td>
                            <td><a href="{{ route('reports.edit', $report) }}">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:1rem;">{{ $reports->links() }}</div>
        @endif
    </div>
@endsection
