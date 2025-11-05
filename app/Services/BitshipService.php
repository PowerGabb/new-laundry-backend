<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BitshipService
{
    private string $apiKey;

    private string $baseUrl;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->apiKey = config('services.biteship.api_key');
        $this->baseUrl = config('services.biteship.base_url');
    }

    /**
     * Get courier rates from Biteship API.
     *
     * @param  array{origin_latitude: float, origin_longitude: float, destination_latitude: float, destination_longitude: float, couriers: string}  $params
     * @return array<string, mixed>
     */
    public function getCourierRates(array $params): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/rates/couriers", [
                'origin_latitude' => $params['origin_latitude'],
                'origin_longitude' => $params['origin_longitude'],
                'destination_latitude' => $params['destination_latitude'],
                'destination_longitude' => $params['destination_longitude'],
                'couriers' => $params['couriers'] ?? 'gojek,grab',
                'items' => $params['items'] ?? [
                    [
                        'name' => 'Laundry',
                        'description' => 'Pakaian laundry',
                        'value' => $params['value'] ?? 50000,
                        'weight' => $params['weight'] ?? 1000,
                        'quantity' => 1,
                    ],
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Biteship API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal mendapatkan tarif kurir',
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Biteship Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi layanan kurir',
                'error' => $e->getMessage(),
            ];
        }
    }
}
