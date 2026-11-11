@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="card" style="max-width:420px;margin:3rem auto;">
        <h1>Create account</h1>
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <label for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <div class="row">
                <button class="btn" type="submit">Register</button>
                <a href="{{ route('login') }}">Back to login</a>
            </div>
        </form>
    </div>
@endsection
