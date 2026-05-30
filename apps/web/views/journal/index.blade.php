@extends('layouts.app', ['title' => 'Journal'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Trade journal</h1>
        <p class="text-secondary mb-0">Historical notes, dividends, and trade records separate from official snapshot math.</p>
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
                        <th>Account</th>
                        <th>Holding</th>
                        <th>Amount</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->trade_date->toDateString() }}</td>
                            <td class="text-capitalize">{{ $entry->entry_type }}</td>
                            <td>{{ $entry->account?->name ?? 'N/A' }}</td>
                            <td>{{ $entry->holding?->asset?->symbol ?? 'N/A' }}</td>
                            <td>{{ $entry->amount ? '$'.number_format((float) $entry->amount, 2) : 'N/A' }}</td>
                            <td>{{ $entry->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-secondary">No journal entries yet. Use the API `POST /api/journal` to record trades and notes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
