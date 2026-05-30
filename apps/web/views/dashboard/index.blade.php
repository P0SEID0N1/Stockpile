@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $portfolio->name }}</h1>
        <p class="text-secondary mb-0">Transaction-driven tracking with historical portfolio valuation.</p>
    </div>
    <div class="text-secondary small">
        Benchmark: <strong>{{ $summary['benchmark_label'] }}</strong>
        @if ($summary['quote_timestamp'])
            <span class="d-block">Quotes updated {{ \Illuminate\Support\Carbon::parse($summary['quote_timestamp'])->diffForHumans() }}</span>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="metric-card">
            <span class="metric-label">Current value</span>
            <strong>${{ number_format($summary['current_value'], 2) }}</strong>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="metric-card">
            <span class="metric-label">Total gain / loss</span>
            <strong class="{{ $summary['total_gain_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                ${{ number_format($summary['total_gain_loss'], 2) }}
            </strong>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="metric-card">
            <span class="metric-label">Day change</span>
            <strong class="{{ $summary['day_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                ${{ number_format($summary['day_change'], 2) }} ({{ number_format($summary['day_change_percent'], 2) }}%)
            </strong>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="metric-card">
            <span class="metric-label">Positions</span>
            <strong>{{ $summary['holdings_count'] }}</strong>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="h5 mb-0">Portfolio value</h2>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Range selector">
                            @foreach (['1d', '5d', '1m', '3m', '1y', 'ytd'] as $range)
                                <a href="{{ route('dashboard', ['range' => $range]) }}" class="btn {{ $selectedRange === $range ? 'btn-dark' : 'btn-outline-dark' }}">{{ strtoupper($range) }}</a>
                            @endforeach
                        </div>
                        <a href="{{ route('performance.index', ['range' => $selectedRange]) }}" class="btn btn-outline-dark btn-sm">Open performance</a>
                    </div>
                </div>
                <canvas id="growthChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Allocation by asset type</h2>
                <div class="vstack gap-3">
                    @foreach ($summary['asset_type_allocation'] as $item)
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-capitalize">{{ $item['label'] }}</span>
                                <span>{{ number_format($item['percentage'], 2) }}%</span>
                            </div>
                            <div class="progress" role="progressbar" aria-label="{{ $item['label'] }}">
                                <div class="progress-bar" style="width: {{ min($item['percentage'], 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Largest holdings</h2>
            <a href="{{ route('portfolio.index') }}" class="btn btn-outline-dark btn-sm">View all holdings</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Account</th>
                        <th>Quantity</th>
                        <th>Cost basis</th>
                        <th>Market value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentHoldings as $holding)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $holding->asset->symbol }}</div>
                                <div class="small text-secondary text-capitalize">{{ $holding->asset->asset_type }}</div>
                            </td>
                            <td>{{ $holding->account->name }}</td>
                            <td>{{ number_format((float) $holding->quantity, 4) }}</td>
                            <td>${{ number_format((float) $holding->cost_basis_total, 2) }}</td>
                            <td>${{ number_format((float) $holding->market_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const dashboardSeries = @json($series['portfolio']);
new Chart(document.getElementById('growthChart'), {
    type: 'line',
    data: {
        labels: dashboardSeries.map(point => point.date),
        datasets: [
            {
                label: 'Portfolio',
                data: dashboardSeries.map(point => point.value),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.15)',
                tension: 0.25,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});
</script>
@endpush
