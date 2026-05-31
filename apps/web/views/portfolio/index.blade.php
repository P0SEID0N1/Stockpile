@extends('layouts.app', ['title' => 'Portfolio'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Accounts and holdings</h1>
        <p class="text-secondary mb-0">Add backdated purchases and track the current position state derived from transactions.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addStockModal">Add stock</button>
        <a href="{{ route('imports.index') }}" class="btn btn-outline-dark">Import CSV snapshot</a>
    </div>
</div>

@if (($summary['quote_source'] ?? null) === 'demo')
    <div class="alert alert-warning border-0 shadow-sm" role="alert">
        Market values are currently using demo quotes, not Tiingo live data. Update <code>MARKET_DATA_PROVIDER=tiingo</code>, set <code>TIINGO_API_TOKEN</code>, then run <code>php artisan optimize:clear</code>, <code>php artisan config:cache</code>, and <code>php artisan portfolio:refresh-quotes</code> on the server.
    </div>
@endif

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
                            <th>Net cash invested</th>
                            <th>Position value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($account->holdings as $holding)
                            <tr>
                                <td class="fw-semibold">{{ $holding->asset->symbol }}</td>
                                <td>{{ $holding->asset->name }}</td>
                                <td class="text-capitalize">{{ $holding->asset->asset_type }}</td>
                                <td>
                                    {{ number_format((float) $holding->quantity, 4) }}
                                    <div class="text-secondary small">@ ${{ number_format($holding->currentPricePerShare(), 2) }}/share</div>
                                </td>
                                <td>
                                    ${{ number_format($holding->manualNetInvestedTotal(), 2) }}
                                    @if (abs($holding->dripBasisAdjustment()) >= 0.01)
                                        <div class="text-secondary small">Tax basis incl. DRIP: ${{ number_format((float) $holding->cost_basis_total, 2) }}</div>
                                    @endif
                                </td>
                                <td>
                                    ${{ number_format((float) $holding->market_value, 2) }}
                                    <div class="text-secondary small">{{ number_format((float) $holding->quantity, 4) }} × ${{ number_format($holding->currentPricePerShare(), 2) }}</div>
                                </td>
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

<div class="modal fade" id="addStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="{{ route('portfolio.holdings.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">Add stock purchase</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Ticker</label>
                            <input type="text" name="symbol" class="form-control" value="{{ old('symbol') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date purchased</label>
                            <input type="date" name="trade_date" class="form-control" value="{{ old('trade_date', $defaultTradeDate) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purchase quantity</label>
                            <input type="number" step="0.000001" min="0.000001" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price paid</label>
                            <input type="number" step="0.000001" min="0.000001" name="purchase_price" class="form-control" value="{{ old('purchase_price') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Total cost</label>
                            <input type="number" step="0.01" min="0.01" name="total_cost" class="form-control" value="{{ old('total_cost') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">Save purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
