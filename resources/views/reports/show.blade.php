@extends('layouts.app')

@section('title', 'Report')

@section('content')
    <div class="row" style="justify-content:space-between;margin-bottom:1rem;">
        <h1 style="margin:0;">Report #{{ $report->id }}</h1>
        <div class="row">
            <a class="btn btn-secondary" href="{{ route('reports.edit', $report) }}">Edit</a>
            <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Delete this report?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Delete</button>
            </form>
        </div>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>Patient name</th>
                <td>{{ $report->patient_name }}</td>
            </tr>
            <tr>
                <th>Refer by (doctor name)</th>
                <td>{{ $report->referred_by_name }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($report->status) }}</td>
            </tr>
            <tr>
                <th>Created</th>
                <td>{{ $report->created_at->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>
@endsection
