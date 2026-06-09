<?php

namespace App\Services;

use App\Models\GlEntity;
use App\Models\GlMappingProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PythonServiceClient
{
    /**
     * Panggil Python service untuk eksekusi extraction.
     *
     * Returns array dengan key:
     * - status: 'success' atau 'failed'
     * - output_file, total_records, total_debit, total_credit (kalau success)
     * - error (kalau failed)
     */
    public function run(GlEntity $entity, GlMappingProfile $profile, int $year, int $month): array
    {
        $url = $entity->getPythonServiceUrl();
        $payload = $this->buildPayload($entity, $profile, $year, $month);

        try {
            $response = Http::timeout(config('services.python.timeout', 120))
                ->acceptJson()
                ->asJson()
                ->post($url . '/run', $payload);

            if (!$response->successful()) {
                $body = $response->json() ?? ['error' => $response->body()];
                return [
                    'status' => 'failed',
                    'error' => $body['error'] ?? 'Python service returned error',
                    'http_status' => $response->status(),
                    'details' => $body,
                ];
            }

            return $response->json();

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'status' => 'failed',
                'error' => "Tidak bisa connect ke Python service di {$url}. Pastikan service jalan.",
                'exception' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('PythonServiceClient error: ' . $e->getMessage(), [
                'entity' => $entity->code,
                'profile' => $profile->name,
                'period' => "{$year}-{$month}",
            ]);
            return [
                'status' => 'failed',
                'error' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cek kesehatan Python service (semarang & surabaya)
     */
    public function health(): array
    {
        $services = [
            'semarang' => config('services.python.semarang_url'),
            'surabaya' => config('services.python.surabaya_url'),
        ];

        $results = [];
        foreach ($services as $region => $url) {
            try {
                $response = Http::timeout(5)->get($url . '/health');
                $results[$region] = [
                    'url' => $url,
                    'online' => $response->successful(),
                    'data' => $response->successful() ? $response->json() : null,
                    'error' => $response->successful() ? null : $response->body(),
                ];
            } catch (\Exception $e) {
                $results[$region] = [
                    'url' => $url,
                    'online' => false,
                    'data' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Build JSON payload yang akan dikirim ke Python service
     */
    private function buildPayload(GlEntity $entity, GlMappingProfile $profile, int $year, int $month): array
    {
        $mappings = $profile->accountMappings()->orderBy('order_index')->get()->map(function ($m) {
            return [
                'mapping_key' => $m->mapping_key,
                'account_number' => $m->account_number,
                'account_name' => $m->account_name,
                'account_type' => $m->account_type,
                'transaction_value' => $m->transaction_value,
                'cost_center' => $m->cost_center,
                'profit_center' => $m->profit_center,
                'use_profit_center' => (bool) $m->use_profit_center,
                'components' => $m->components ?: [],
                'match_account_name' => $m->match_account_name,
                'match_keywords' => $m->match_keywords ?: [],
                'order_index' => $m->order_index,
            ];
        })->toArray();

        $payload = [
            'entity_code' => $entity->code,
            'entity_name' => $entity->name,
            'ledger_code' => $entity->ledger_code,
            'branch_id' => $entity->branch_id,
            'ledger_id_strategy' => $entity->ledger_id_strategy,
            'ledger_id_list' => $entity->ledger_id_list ?: [900],
            'doc_header_template' => $entity->doc_header_template,
            'output_filename_template' => $entity->output_filename_template,
            'extraction_strategy' => $entity->extraction_strategy,
            'company_code' => $entity->company_code,
            'year' => $year,
            'month' => $month,
            'mappings' => $mappings,
            'talenta' => [
                'client_id' => config('services.talenta.client_id', env('TALENTA_CLIENT_ID')),
                'client_secret' => config('services.talenta.client_secret', env('TALENTA_CLIENT_SECRET')),
            ],
        ];

        // Strategy D butuh konfigurasi khusus
        if ($entity->extraction_strategy === 'D') {
            $dConfig = $profile->strategyDConfig;
            if ($dConfig) {
                $payload['strategy_d_config'] = [
                    'debit_accounts' => $dConfig->debit_accounts ?: [],
                    'debit_keywords' => $dConfig->debit_keywords ?: ['pengembalian'],
                    'default_dc' => $dConfig->default_dc ?: 'Credit',
                ];
            }
        }

        return $payload;
    }
}