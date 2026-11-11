@extends('layouts.app')

@section('title', 'Add doctor')

@section('content')
    <h1>Add doctor</h1>
    <div class="card">
        <form method="POST" action="{{ route('doctors.store') }}">
            @csrf
            @include('doctors._form')
            <div class="row">
                <button class="btn" type="submit">Save doctor</button>
                <a class="btn btn-secondary" href="{{ route('doctors.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
