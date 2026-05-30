@extends('layouts.app', ['title' => 'Performance'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Performance analytics</h1>
        <p class="text-secondary mb-0">Practical snapshot analytics with benchmark comparison.</p>
    </div>
    <div class="small text-secondary">Benchmark: <strong>{{ $portfolio->benchmark_symbol }}</strong></div>
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
                    <dt class="col-6">Cost basis</dt>
                    <dd class="col-6 text-end">${{ number_format($summary['cost_basis_total'], 2) }}</dd>
                    <dt class="col-6">Gain / loss</dt>
                    <dd class="col-6 text-end {{ $summary['total_gain_loss'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($summary['total_gain_loss'], 2) }}</dd>
                    <dt class="col-6">Day change</dt>
                    <dd class="col-6 text-end {{ $summary['day_change'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($summary['day_change'], 2) }}</dd>
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
const perfSeries = @json($series['portfolio']);
const perfBenchmark = @json($series['benchmark']);
new Chart(document.getElementById('performanceChart'), {
    type: 'line',
    data: {
        labels: perfSeries.map(point => point.date),
        datasets: [
            {
                label: 'Portfolio value',
                data: perfSeries.map(point => point.value),
                borderColor: '#0dcaf0',
                backgroundColor: 'rgba(13, 202, 240, 0.15)',
                fill: true,
                tension: 0.2
            },
            {
                label: 'Benchmark (index 100)',
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
