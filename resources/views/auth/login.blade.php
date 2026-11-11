@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="card" style="max-width:420px;margin:3rem auto;">
        <h1>Login</h1>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <div class="checkbox">
                <input id="remember" type="checkbox" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <div class="row">
                <button class="btn" type="submit">Sign in</button>
                <a href="{{ route('register') }}">Create account</a>
            </div>
        </form>
        <p class="muted" style="margin-top:1rem;font-size:0.9rem;">Demo: test@example.com / password</p>
    </div>
@endsection
