@extends('layouts.app')

@section('title', 'Edit doctor')

@section('content')
    <h1>Edit doctor</h1>
    <div class="card">
        <form method="POST" action="{{ route('doctors.update', $doctor) }}">
            @csrf
            @method('PUT')
            @include('doctors._form', ['doctor' => $doctor])
            <div class="row">
                <button class="btn" type="submit">Update doctor</button>
                <a class="btn btn-secondary" href="{{ route('doctors.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
