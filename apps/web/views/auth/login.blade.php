@extends('layouts.app', ['title' => 'Sign In'])

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Sign in</h1>
                <p class="text-secondary">Access your self-hosted Stockpile dashboard.</p>
                <form method="post" action="{{ route('login.store') }}" class="vstack gap-3">
                    @csrf
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-dark">Sign in</button>
                </form>
                @if (!\App\Models\User::query()->exists())
                    <div class="mt-3">
                        <a href="{{ route('setup') }}" class="link-secondary">Create the first account</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
