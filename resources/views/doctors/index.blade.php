@extends('layouts.app')

@section('title', 'Doctors')

@section('content')
    <div class="row" style="justify-content:space-between;margin-bottom:1rem;">
        <h1 style="margin:0;">My doctors</h1>
        <a class="btn" href="{{ route('doctors.create') }}">Add doctor</a>
    </div>

    <div class="card">
        <p class="muted">Each account keeps its own referring-doctor list for reports.</p>
        @if ($doctors->isEmpty())
            <p>No doctors yet. Add one to use on reports.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Specialization</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($doctors as $doctor)
                        <tr>
                            <td>{{ $doctor->name }}</td>
                            <td>{{ $doctor->phone ?: '—' }}</td>
                            <td>{{ $doctor->specialization ?: '—' }}</td>
                            <td>{{ $doctor->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="row">
                                <a href="{{ route('doctors.edit', $doctor) }}">Edit</a>
                                <form method="POST" action="{{ route('doctors.destroy', $doctor) }}" onsubmit="return confirm('Remove this doctor?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:1rem;">{{ $doctors->links() }}</div>
        @endif
    </div>
@endsection
