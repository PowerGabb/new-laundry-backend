<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Create payment for an order.
     */
    public function pay(Order $order, MidtransService $midtransService): JsonResponse
    {
        // Check if user owns the order
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke order ini',
            ], 403);
        }

        // Check if order status is ready for payment
        if (! in_array($order->order_status, ['ready', 'completed', 'delivering'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order belum siap untuk dibayar. Status order harus "ready", "delivering", atau "completed".',
            ], 400);
        }

        // Check if already paid
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order sudah dibayar',
            ], 400);
        }

        // Load branch and user data
        $order->load(['branch', 'user']);

        // Prepare transaction details for Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $order->total_amount,
            ],
            'customer_details' => [
                'first_name' => $order->customer_name ?? $order->user->name,
                'email' => $order->user->email,
                'phone' => $order->customer_phone,
            ],
            'item_details' => [
                [
                    'id' => 'laundry-service',
                    'price' => $order->subtotal,
                    'quantity' => 1,
                    'name' => "Laundry Service - {$order->branch->name}",
                ],
            ],
        ];

        // Add pickup shipping fee if exists
        if ($order->pickup_shipping_fee > 0) {
            $params['item_details'][] = [
                'id' => 'pickup-fee',
                'price' => $order->pickup_shipping_fee,
                'quantity' => 1,
                'name' => 'Biaya Pickup',
            ];
        }

        // Add delivery fee if exists
        if ($order->delivery_fee > 0) {
            $params['item_details'][] = [
                'id' => 'delivery-fee',
                'price' => $order->delivery_fee,
                'quantity' => 1,
                'name' => 'Biaya Delivery',
            ];
        }

        // Create snap token
        $result = $midtransService->createSnapToken($params);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], 500);
        }

        // Update order with snap token
        $order->update([
            'snaptoken' => $result['snap_token'],
            'payment_method' => 'midtrans',
            'payment_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Snap token berhasil dibuat',
            'data' => [
                'snaptoken' => $result['snap_token'],
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
            ],
        ]);
    }

    /**
     * Handle payment notification from Midtrans.
     */
    public function notification(Request $request): JsonResponse
    {
        try {
            // Create notification object
            $notification = new \Midtrans\Notification();

            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status;
            $orderNumber = $notification->order_id;

            // Find order
            $order = Order::where('order_number', $orderNumber)->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Handle transaction status
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    // Payment success
                    $order->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                } elseif ($fraudStatus == 'challenge') {
                    // Payment challenged
                    $order->update([
                        'payment_status' => 'pending',
                    ]);
                }
            } elseif ($transactionStatus == 'settlement') {
                // Payment settled
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            } elseif ($transactionStatus == 'pending') {
                // Payment pending
                $order->update([
                    'payment_status' => 'pending',
                ]);
            } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                // Payment failed
                $order->update([
                    'payment_status' => 'failed',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification handled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to handle notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check payment status for an order.
     */
    public function status(Order $order, MidtransService $midtransService): JsonResponse
    {
        // Check if user owns the order
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke order ini',
            ], 403);
        }

        // Get transaction status from Midtrans
        $result = $midtransService->getTransactionStatus($order->order_number);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status transaksi berhasil didapatkan',
            'data' => [
                'order' => [
                    'order_number' => $order->order_number,
                    'payment_status' => $order->payment_status,
                    'total_amount' => $order->total_amount,
                ],
                'midtrans' => $result['data'],
            ],
        ]);
    }
}
