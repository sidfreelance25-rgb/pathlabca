<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pathology Lab') — {{ config('app.name') }}</title>
    <style>
        :root {
            --bg: #f4f7f5;
            --surface: #ffffff;
            --ink: #15231c;
            --muted: #5b6b62;
            --line: #d7e0da;
            --accent: #0f6b4c;
            --accent-soft: #e7f4ee;
            --danger: #9b1c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(180deg, #eef5f1 0%, var(--bg) 220px);
            color: var(--ink);
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { max-width: 960px; margin: 0 auto; padding: 1.25rem; }
        .nav {
            display: flex; gap: 1rem; align-items: center; justify-content: space-between;
            margin-bottom: 1.5rem; padding: 0.85rem 1rem; background: var(--surface);
            border: 1px solid var(--line); border-radius: 12px;
        }
        .nav-links { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .brand { font-weight: 700; color: var(--ink); }
        .card {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem;
        }
        .flash {
            background: var(--accent-soft); border: 1px solid #b7dcc9;
            color: var(--accent); padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem;
        }
        .errors {
            background: #fdecec; border: 1px solid #f3c1c1; color: var(--danger);
            padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem;
        }
        label { display: block; font-size: 0.9rem; margin-bottom: 0.35rem; color: var(--muted); }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--line);
            border-radius: 8px; margin-bottom: 0.9rem; font: inherit; background: #fff;
        }
        .row { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
        .btn {
            display: inline-block; border: 0; border-radius: 8px; padding: 0.6rem 0.95rem;
            background: var(--accent); color: #fff; font: inherit; cursor: pointer;
        }
        .btn-secondary { background: #e8eeea; color: var(--ink); }
        .btn-danger { background: var(--danger); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.7rem 0.4rem; border-bottom: 1px solid var(--line); }
        th { color: var(--muted); font-weight: 600; font-size: 0.85rem; }
        .muted { color: var(--muted); }
        h1 { margin: 0 0 1rem; font-size: 1.5rem; }
        .checkbox { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.9rem; }
        .checkbox input { margin: 0; }
        .checkbox label { margin: 0; color: var(--ink); }
    </style>
</head>
<body>
    <div class="shell">
        @auth
            <nav class="nav">
                <div class="nav-links">
                    <a class="brand" href="{{ route('reports.index') }}">Pathology Lab</a>
                    <a href="{{ route('reports.index') }}">Reports</a>
                    <a href="{{ route('doctors.index') }}">Doctors</a>
                </div>
                <div class="nav-links">
                    <span class="muted">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-secondary" type="submit">Log out</button>
                    </form>
                </div>
            </nav>
        @endauth

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">
                <ul style="margin:0; padding-left:1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
