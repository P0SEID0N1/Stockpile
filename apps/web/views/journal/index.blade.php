@extends('layouts.app', ['title' => 'Journal'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Transaction ledger</h1>
        <p class="text-secondary mb-0">Every buy, sell, dividend, and dividend reinvestment recorded for this portfolio.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Symbol</th>
                        <th>Account</th>
                        <th>Shares</th>
                        <th>Price</th>
                        <th>Cash amount</th>
                        <th>Source</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->trade_date->toDateString() }}</td>
                            <td class="text-capitalize">{{ $entry->entry_type }}</td>
                            <td>{{ $entry->asset?->symbol ?? $entry->holding?->asset?->symbol ?? 'N/A' }}</td>
                            <td>{{ $entry->account?->name ?? 'N/A' }}</td>
                            <td>{{ $entry->quantity ? number_format((float) $entry->quantity, 6) : '—' }}</td>
                            <td>{{ $entry->price_per_unit ? '$'.number_format((float) $entry->price_per_unit, 4) : '—' }}</td>
                            <td>{{ $entry->amount ? '$'.number_format((float) $entry->amount, 2) : '—' }}</td>
                            <td>{{ str_replace('_', ' ', $entry->source_type) }}</td>
                            <td>{{ $entry->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-secondary">No transactions yet. Add a stock purchase to begin building the portfolio ledger.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
