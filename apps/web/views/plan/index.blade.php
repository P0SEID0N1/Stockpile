@extends('layouts.app', ['title' => 'Plan'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Allocation planning</h1>
        <p class="text-secondary mb-0">Target allocation deltas based on current snapshot value.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Current portfolio value</h2>
            <span class="fs-5 fw-semibold">${{ number_format($planner['current_value'], 2) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Target</th>
                        <th>Target %</th>
                        <th>Current %</th>
                        <th>Current value</th>
                        <th>Target value</th>
                        <th>Delta</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($planner['targets'] as $target)
                        <tr>
                            <td>{{ $target['label'] }}</td>
                            <td>{{ number_format($target['target_percentage'], 2) }}%</td>
                            <td>{{ number_format($target['current_percentage'], 2) }}%</td>
                            <td>${{ number_format($target['current_value'], 2) }}</td>
                            <td>${{ number_format($target['target_value'], 2) }}</td>
                            <td class="{{ $target['delta_value'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($target['delta_value'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-secondary">No targets configured yet. Use the API `PUT /api/plans/targets` to define them.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
