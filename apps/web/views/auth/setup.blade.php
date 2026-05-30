@extends('layouts.app', ['title' => 'First-Time Setup'])

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h3 mb-3">Create your first account</h1>
                <p class="text-secondary">This sets up the initial personal user and a default portfolio.</p>
                <form method="post" action="{{ route('setup.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-dark">Create account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
