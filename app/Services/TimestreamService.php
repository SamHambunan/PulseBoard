<?php

namespace App\Services;

use Aws\TimestreamWrite\TimestreamWriteClient;
use Aws\TimestreamQuery\TimestreamQueryClient;
use Illuminate\Support\Facades\Log;

class TimestreamService
{
    private ?TimestreamWriteClient $writer = null;
    private ?TimestreamQueryClient $querier = null;
    private bool $isLocal;

    public function __construct()
    {
        // Check if we are in local/testing to avoid AWS credential errors
        $this->isLocal = app()->environment('local', 'testing');

        if (! $this->isLocal) {
            $this->writer = new TimestreamWriteClient(['version' => 'latest', 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')]);
            $this->querier = new TimestreamQueryClient(['version' => 'latest', 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')]);
        }
    }

    public function write(string $tenantId, string $metric, float $value, ?float $delta, string $source, int $ts): void
    {
        if ($this->isLocal) {
            Log::info("Mock Timestream Write: [$tenantId] $metric = $value");
            return;
        }

        $this->writer->writeRecords([
            'DatabaseName' => env('AWS_TIMESTREAM_DB', 'pulseboard'),
            'TableName'    => 'metrics',
            'Records'      => [[
                'Dimensions' => [
                    ['Name' => 'tenant_id', 'Value' => $tenantId],
                    ['Name' => 'metric',    'Value' => $metric],
                    ['Name' => 'source',    'Value' => $source],
                ],
                'MeasureName'  => 'value',
                'MeasureValue' => (string) $value,
                'MeasureValueType' => 'DOUBLE',
                'Time'         => (string) $ts,
                'TimeUnit'     => 'MILLISECONDS',
            ]],
        ]);
    }

    public function query(string $tenantId, string $metric, string $range): array
    {
        if ($this->isLocal) {
            // Mock data for local development charts
            $now = round(microtime(true) * 1000);
            return [
                ['ts' => $now - 300000, 'value' => rand(40, 50)],
                ['ts' => $now - 150000, 'value' => rand(50, 60)],
                ['ts' => $now,          'value' => rand(60, 70)],
            ];
        }

        $interval = match ($range) {
            '5m'  => '5 MINUTES',
            '1h'  => '1 HOURS',
            '24h' => '24 HOURS',
            '7d'  => '7 DAYS',
            default => '1 HOURS',
        };

        $db = env('AWS_TIMESTREAM_DB', 'pulseboard');
        $sql = "SELECT time, measure_value::double AS value FROM \"{$db}\".\"metrics\" WHERE tenant_id = '{$tenantId}' AND metric = '{$metric}' AND time > ago({$interval}) ORDER BY time ASC";

        $result = $this->querier->query(['QueryString' => $sql]);

        return collect($result['Rows'])
            ->map(fn($row) => [
                'ts'    => strtotime($row['Data'][0]['ScalarValue']) * 1000,
                'value' => (float) $row['Data'][1]['ScalarValue'],
            ])
            ->all();
    }
}
