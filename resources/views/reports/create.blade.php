@extends('layouts.app')

@section('title', 'New report')

@section('content')
    <h1>New report</h1>
    <div class="card">
        <form method="POST" action="{{ route('reports.store') }}">
            @csrf
            @include('reports._form')
            <div class="row">
                <button class="btn" type="submit">Create report</button>
                <a class="btn btn-secondary" href="{{ route('reports.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
