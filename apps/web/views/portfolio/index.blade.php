@extends('layouts.app', ['title' => 'Portfolio'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Accounts and holdings</h1>
        <p class="text-secondary mb-0">Current snapshot positions grouped by account.</p>
    </div>
    <a href="{{ route('imports.index') }}" class="btn btn-dark">Import CSV snapshot</a>
</div>

@foreach ($accounts as $account)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ $account->name }}</h2>
                    <div class="text-secondary small text-capitalize">{{ $account->type }} @if($account->institution) · {{ $account->institution }} @endif</div>
                </div>
                <span class="badge text-bg-light">{{ $account->currency }}</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Cost basis</th>
                            <th>Market value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($account->holdings as $holding)
                            <tr>
                                <td class="fw-semibold">{{ $holding->asset->symbol }}</td>
                                <td>{{ $holding->asset->name }}</td>
                                <td class="text-capitalize">{{ $holding->asset->asset_type }}</td>
                                <td>{{ number_format((float) $holding->quantity, 4) }}</td>
                                <td>${{ number_format((float) $holding->cost_basis_total, 2) }}</td>
                                <td>${{ number_format((float) $holding->market_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-secondary">No holdings imported yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
@endsection
