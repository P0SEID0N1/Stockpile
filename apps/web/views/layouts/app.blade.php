<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Stockpile' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @php($route = request()->route()?->getName())

    <nav class="navbar navbar-expand-lg border-bottom bg-white sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                <img src="{{ asset('brand/stockpile-mark.svg') }}" alt="" width="28" height="28">
                <span>Stockpile</span>
            </a>
            @auth
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#stockpileNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="stockpileNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link {{ str_starts_with($route, 'dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ str_starts_with($route, 'portfolio') ? 'active' : '' }}" href="{{ route('portfolio.index') }}">Portfolio</a></li>
                        <li class="nav-item"><a class="nav-link {{ str_starts_with($route, 'performance') ? 'active' : '' }}" href="{{ route('performance.index') }}">Performance</a></li>
                        <li class="nav-item"><a class="nav-link {{ str_starts_with($route, 'journal') ? 'active' : '' }}" href="{{ route('journal.index') }}">Journal</a></li>
                        <li class="nav-item"><a class="nav-link {{ str_starts_with($route, 'plan') ? 'active' : '' }}" href="{{ route('plan.index') }}">Plan</a></li>
                        <li class="nav-item"><a class="nav-link {{ str_starts_with($route, 'imports') ? 'active' : '' }}" href="{{ route('imports.index') }}">Imports</a></li>
                        <li class="nav-item"><a class="nav-link {{ str_starts_with($route, 'settings') ? 'active' : '' }}" href="{{ route('settings.index') }}">Settings</a></li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-secondary small">{{ auth()->user()->email }}</span>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-dark btn-sm">Sign out</button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </nav>

    <main class="container-fluid px-4 py-4">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>
</html>
