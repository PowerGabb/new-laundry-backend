<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        // Set Midtrans configuration
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    /**
     * Create Snap token for payment.
     *
     * @param  array  $params
     * @return array
     */
    public function createSnapToken(array $params): array
    {
        try {
            $snapToken = Snap::getSnapToken($params);

            return [
                'success' => true,
                'snap_token' => $snapToken,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create snap token',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction status from Midtrans.
     *
     * @param  string  $orderId
     * @return array
     */
    public function getTransactionStatus(string $orderId): array
    {
        try {
            $status = \Midtrans\Transaction::status($orderId);

            return [
                'success' => true,
                'data' => $status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get transaction status',
                'error' => $e->getMessage(),
            ];
        }
    }
}
