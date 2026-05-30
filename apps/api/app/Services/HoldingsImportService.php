<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Holding;
use App\Models\HoldingSnapshot;
use App\Models\ImportJob;
use App\Models\Portfolio;
use App\Models\PriceQuote;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HoldingsImportService
{
    private const REQUIRED_HEADERS = [
        'account_name',
        'account_type',
        'symbol',
        'asset_type',
        'quantity',
        'cost_basis_total',
        'snapshot_date',
    ];

    public function preview(User $user, Portfolio $portfolio, UploadedFile $file): ImportJob
    {
        $rows = $this->parseFile($file);
        $summary = [
            'row_count' => count($rows),
            'account_count' => count(array_unique(array_column($rows, 'account_name'))),
            'symbol_count' => count(array_unique(array_column($rows, 'symbol'))),
        ];

        return ImportJob::query()->create([
            'user_id' => $user->id,
            'portfolio_id' => $portfolio->id,
            'filename' => $file->getClientOriginalName(),
            'status' => 'preview',
            'preview_payload' => [
                'summary' => $summary,
                'rows' => $rows,
            ],
        ]);
    }

    public function commit(ImportJob $importJob): ImportJob
    {
        if ($importJob->status === 'committed') {
            return $importJob;
        }

        $rows = $importJob->preview_payload['rows'] ?? [];

        DB::transaction(function () use ($importJob, $rows) {
            foreach ($rows as $row) {
                $account = Account::query()->firstOrCreate(
                    [
                        'portfolio_id' => $importJob->portfolio_id,
                        'name' => $row['account_name'],
                    ],
                    [
                        'type' => $row['account_type'],
                        'currency' => $row['currency'] ?? 'USD',
                        'institution' => null,
                    ],
                );

                $asset = Asset::query()->firstOrCreate(
                    [
                        'symbol' => $row['symbol'],
                        'asset_type' => $row['asset_type'],
                    ],
                    [
                        'name' => $row['name'] ?: $row['symbol'],
                        'currency' => $row['currency'] ?? 'USD',
                        'sector' => $row['sector'] ?: null,
                        'notes' => $row['notes'] ?: null,
                        'metadata' => array_filter([
                            'coupon_rate' => $row['coupon_rate'] ?: null,
                            'maturity_date' => $row['maturity_date'] ?: null,
                        ]),
                    ],
                );

                $latestQuote = PriceQuote::query()
                    ->where('asset_id', $asset->id)
                    ->latest('quoted_at')
                    ->first();
                $quantity = (float) $row['quantity'];
                $costBasis = (float) $row['cost_basis_total'];
                $marketValue = $latestQuote
                    ? round($quantity * (float) $latestQuote->price, 2)
                    : round($costBasis, 2);

                $holding = Holding::query()->updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'asset_id' => $asset->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'cost_basis_total' => $costBasis,
                        'market_value' => $marketValue,
                        'price_as_of' => $latestQuote?->quoted_at,
                        'last_snapshot_at' => $row['snapshot_date'],
                        'notes' => $row['notes'] ?: null,
                    ],
                );

                HoldingSnapshot::query()->updateOrCreate(
                    [
                        'holding_id' => $holding->id,
                        'snapshot_date' => $row['snapshot_date'],
                    ],
                    [
                        'import_job_id' => $importJob->id,
                        'quantity' => $quantity,
                        'cost_basis_total' => $costBasis,
                        'market_value' => $marketValue,
                        'price_per_unit' => $quantity > 0 ? round($marketValue / $quantity, 6) : null,
                        'source_type' => 'csv',
                        'source_reference' => $importJob->filename,
                    ],
                );
            }
        });

        $importJob->forceFill([
            'status' => 'committed',
            'imported_rows' => count($rows),
            'failed_rows' => 0,
            'committed_at' => now(),
            'result_payload' => [
                'message' => 'Import committed successfully.',
            ],
        ])->save();

        return $importJob->refresh();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded CSV file could not be read.',
            ]);
        }

        $header = fgetcsv($handle);
        $header = array_map(fn ($column) => strtolower(trim((string) $column)), $header ?: []);
        $missing = array_diff(self::REQUIRED_HEADERS, $header);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Missing required CSV columns: '.implode(', ', $missing),
            ]);
        }

        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }

            $row = array_combine($header, $line);
            $normalized = [
                'account_name' => trim((string) ($row['account_name'] ?? '')),
                'account_type' => trim((string) ($row['account_type'] ?? '')),
                'symbol' => strtoupper(trim((string) ($row['symbol'] ?? ''))),
                'asset_type' => strtolower(trim((string) ($row['asset_type'] ?? ''))),
                'quantity' => (float) ($row['quantity'] ?? 0),
                'cost_basis_total' => round((float) ($row['cost_basis_total'] ?? 0), 2),
                'snapshot_date' => (string) ($row['snapshot_date'] ?? ''),
                'currency' => strtoupper(trim((string) ($row['currency'] ?? 'USD'))),
                'name' => trim((string) ($row['name'] ?? '')),
                'sector' => trim((string) ($row['sector'] ?? '')),
                'notes' => trim((string) ($row['notes'] ?? '')),
                'coupon_rate' => trim((string) ($row['coupon_rate'] ?? '')),
                'maturity_date' => trim((string) ($row['maturity_date'] ?? '')),
            ];

            if (
                $normalized['account_name'] === '' ||
                $normalized['account_type'] === '' ||
                $normalized['symbol'] === '' ||
                $normalized['asset_type'] === '' ||
                $normalized['snapshot_date'] === ''
            ) {
                throw ValidationException::withMessages([
                    'file' => 'Every row must include account, symbol, asset type, and snapshot date values.',
                ]);
            }

            $rows[] = $normalized;
        }

        fclose($handle);

        return $rows;
    }
}
