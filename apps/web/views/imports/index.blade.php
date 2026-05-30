@extends('layouts.app', ['title' => 'Imports'])

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h1 class="h4 mb-3">Import holdings CSV</h1>
                <p class="text-secondary">Upload a generic holdings snapshot file to preview and commit account positions.</p>
                <form method="post" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="vstack gap-3">
                    @csrf
                    <div>
                        <label class="form-label">CSV file</label>
                        <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <button class="btn btn-dark">Upload preview</button>
                </form>
                <hr>
                <h2 class="h6">Required columns</h2>
                <code class="small d-block">account_name, account_type, symbol, asset_type, quantity, cost_basis_total, snapshot_date</code>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h2 class="h5 mb-3">Import history</h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Status</th>
                                <th>Rows</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($imports as $import)
                                <tr>
                                    <td>{{ $import->filename }}</td>
                                    <td><span class="badge text-bg-light text-uppercase">{{ $import->status }}</span></td>
                                    <td>{{ $import->preview_payload['summary']['row_count'] ?? 0 }}</td>
                                    <td>{{ $import->created_at->diffForHumans() }}</td>
                                    <td class="text-end">
                                        @if ($import->status !== 'committed')
                                            <form method="post" action="{{ route('imports.commit', $import) }}">
                                                @csrf
                                                <button class="btn btn-outline-dark btn-sm">Commit import</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-secondary">No imports yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (session('import_job_id'))
                    @php($preview = $imports->firstWhere('id', session('import_job_id')))
                    @if ($preview)
                        <hr>
                        <h3 class="h6">Latest preview</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th>Symbol</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Cost basis</th>
                                        <th>Snapshot date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview->preview_payload['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['account_name'] }}</td>
                                            <td>{{ $row['symbol'] }}</td>
                                            <td>{{ $row['asset_type'] }}</td>
                                            <td>{{ $row['quantity'] }}</td>
                                            <td>${{ number_format((float) $row['cost_basis_total'], 2) }}</td>
                                            <td>{{ $row['snapshot_date'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
