@extends('layouts.app')

@section('title', 'Edit report')

@section('content')
    <h1>Edit report</h1>
    <div class="card">
        <form method="POST" action="{{ route('reports.update', $report) }}">
            @csrf
            @method('PUT')
            @include('reports._form', ['report' => $report])
            <div class="row">
                <button class="btn" type="submit">Save changes</button>
                <a class="btn btn-secondary" href="{{ route('reports.show', $report) }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
