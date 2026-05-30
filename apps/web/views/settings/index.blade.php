@extends('layouts.app', ['title' => 'Settings'])

@section('content')
<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h1 class="h4 mb-3">Account settings</h1>
                <dl class="row mb-0">
                    <dt class="col-4">Name</dt>
                    <dd class="col-8">{{ $user->name }}</dd>
                    <dt class="col-4">Email</dt>
                    <dd class="col-8">{{ $user->email }}</dd>
                    <dt class="col-4">Timezone</dt>
                    <dd class="col-8">{{ $user->timezone }}</dd>
                    <dt class="col-4">Currency</dt>
                    <dd class="col-8">{{ $user->preferred_currency }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h2 class="h5 mb-3">Portfolio defaults</h2>
                <dl class="row mb-0">
                    <dt class="col-5">Portfolio</dt>
                    <dd class="col-7">{{ $portfolio->name }}</dd>
                    <dt class="col-5">Base currency</dt>
                    <dd class="col-7">{{ $portfolio->base_currency }}</dd>
                    <dt class="col-5">Benchmark</dt>
                    <dd class="col-7">{{ $portfolio->benchmark_symbol }} · {{ $portfolio->benchmark_name }}</dd>
                </dl>
                <div class="alert alert-secondary mt-4 mb-0">
                    Update benchmark selection through the API endpoint <code>POST /api/benchmarks/select</code>.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
