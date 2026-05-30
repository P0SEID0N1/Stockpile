@extends('layouts.app', ['title' => 'Performance'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Performance vs US market</h1>
        <p class="text-secondary mb-0">Compare your indexed portfolio return against a Wilshire 5000-style broad US benchmark.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="Range selector">
            @foreach (['1d', '5d', '1m', '3m', '1y', 'ytd'] as $range)
                <a href="{{ route('performance.index', ['range' => $range]) }}" class="btn {{ $selectedRange === $range ? 'btn-dark' : 'btn-outline-dark' }}">{{ strtoupper($range) }}</a>
            @endforeach
        </div>
        <div class="small text-secondary">Benchmark: <strong>{{ $series['benchmark_label'] }}</strong></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <canvas id="performanceChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h2 class="h5 mb-3">Summary</h2>
                <dl class="row mb-0">
                    <dt class="col-6">Current value</dt>
                    <dd class="col-6 text-end">${{ number_format($summary['current_value'], 2) }}</dd>
                    <dt class="col-6">Portfolio return</dt>
                    <dd class="col-6 text-end {{ $series['portfolio_return_percent'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($series['portfolio_return_percent'], 2) }}%</dd>
                    <dt class="col-6">Benchmark return</dt>
                    <dd class="col-6 text-end {{ $series['benchmark_return_percent'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($series['benchmark_return_percent'], 2) }}%</dd>
                    <dt class="col-6">Outperformance</dt>
                    <dd class="col-6 text-end {{ ($series['portfolio_return_percent'] - $series['benchmark_return_percent']) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($series['portfolio_return_percent'] - $series['benchmark_return_percent'], 2) }}%</dd>
                </dl>
            </div>
        </div>
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Account allocation</h2>
                <ul class="list-group list-group-flush">
                    @foreach ($summary['account_allocation'] as $item)
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>{{ $item['label'] }}</span>
                            <span>{{ number_format($item['percentage'], 2) }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const perfSeries = @json($series['comparison_portfolio']);
const perfBenchmark = @json($series['benchmark']);
new Chart(document.getElementById('performanceChart'), {
    type: 'line',
    data: {
        labels: perfSeries.map(point => point.date),
        datasets: [
            {
                label: 'Portfolio (index 100)',
                data: perfSeries.map(point => point.value),
                borderColor: '#0dcaf0',
                backgroundColor: 'rgba(13, 202, 240, 0.15)',
                fill: true,
                tension: 0.2
            },
            {
                label: 'US market benchmark',
                data: perfBenchmark.map(point => point.value),
                borderColor: '#6f42c1',
                fill: false,
                tension: 0.2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
@endpush
