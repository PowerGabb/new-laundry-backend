<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send WhatsApp message via Fonnte API
     *
     * @param  string  $phone  Phone number with country code (e.g., 628123456789)
     * @param  string  $message  Message text
     */
    public function sendMessage(string $phone, string $message): bool
    {
        $apiKey = config('services.fonnte.api_key');

        if (! $apiKey) {
            Log::warning('Fonnte API key not configured');

            return false;
        }

        try {
            // Format phone number (remove + and spaces)
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Ensure phone starts with 62 (Indonesia)
            if (substr($phone, 0, 1) === '0') {
                $phone = '62'.substr($phone, 1);
            } elseif (substr($phone, 0, 2) !== '62') {
                $phone = '62'.$phone;
            }

            $response = Http::withHeaders([
                'Authorization' => $apiKey,
            ])->post('https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $phone,
                    'response' => $response->json(),
                ]);

                return true;
            }

            Log::error('Failed to send WhatsApp message', [
                'phone' => $phone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp service exception', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send notification to customer about order status update
     */
    public function notifyCustomerStatusUpdate(object $order): bool
    {
        $statusMessages = [
            'pending' => 'Pesanan Anda sedang menunggu konfirmasi.',
            'processing' => 'Pesanan Anda sedang diproses oleh laundry.',
            'washing' => 'Cucian Anda sedang dicuci. 🧺',
            'ready' => 'Cucian Anda sudah selesai dan siap diambil! ✨',
            'picked_up' => 'Cucian Anda sudah diambil oleh kurir.',
            'delivering' => 'Cucian Anda sedang dalam perjalanan ke alamat Anda. 🚗',
            'completed' => 'Pesanan selesai! Terima kasih telah menggunakan layanan kami. 😊',
            'cancelled' => 'Pesanan Anda dibatalkan.',
        ];

        $statusText = $statusMessages[$order->order_status] ?? 'Status pesanan diperbarui.';

        $message = "*Update Status Pesanan*\n\n";
        $message .= "Halo *{$order->customer_name}*,\n\n";
        $message .= "📦 Order: *{$order->order_number}*\n";
        $message .= "🏷️ Status: *{$statusText}*\n";
        $message .= "📍 Cabang: *{$order->branch->name}*\n\n";

        if ($order->order_status === 'ready') {
            $message .= "Silakan pilih metode pengambilan cucian Anda melalui aplikasi.\n\n";
        }

        if ($order->notes) {
            $message .= "📝 Catatan: {$order->notes}\n\n";
        }

        $message .= 'Terima kasih! 🙏';

        return $this->sendMessage($order->customer_phone, $message);
    }

    /**
     * Send notification to branch about new order
     */
    public function notifyBranchNewOrder(object $order): bool
    {
        $pickupMethod = match ($order->pickup_method) {
            'free_pickup' => '🚗 Penjemputan Gratis',
            'gojek' => '🏍️ GoSend',
            'grab' => '🏍️ GrabExpress',
            default => '📍 Antar Sendiri',
        };

        $message = "*Pesanan Baru Masuk!* 🎉\n\n";
        $message .= "📦 Order: *{$order->order_number}*\n";
        $message .= "👤 Customer: *{$order->customer_name}*\n";
        $message .= "📞 Telp: {$order->customer_phone}\n";
        $message .= "⚖️ Estimasi Berat: *{$order->estimated_weight} kg*\n";
        $message .= '💰 Subtotal: *Rp '.number_format($order->subtotal, 0, ',', '.')."*\n";
        $message .= "🚚 Pickup: {$pickupMethod}\n";

        if ($order->pickup_scheduled_time) {
            $message .= "⏰ Jadwal Pickup: {$order->pickup_scheduled_time}\n";
        }

        $message .= "\n📍 *Alamat Pickup:*\n{$order->customer_address}\n";

        if ($order->special_instructions) {
            $message .= "\n📝 *Instruksi Khusus:*\n{$order->special_instructions}\n";
        }

        if ($order->notes) {
            $message .= "\n💬 Catatan: {$order->notes}\n";
        }

        $message .= "\n_Segera proses pesanan ini melalui aplikasi admin._";

        return $this->sendMessage($order->branch->phone, $message);
    }

    /**
     * Send notification to customer about actual weight update
     */
    public function notifyCustomerActualWeightUpdate(object $order): bool
    {
        $message = "*Update Berat Aktual* ⚖️\n\n";
        $message .= "Halo *{$order->customer_name}*,\n\n";
        $message .= "📦 Order: *{$order->order_number}*\n";
        $message .= "📍 Cabang: *{$order->branch->name}*\n\n";

        $message .= "Cucian Anda sudah ditimbang dengan hasil:\n\n";

        // Show items if available
        if ($order->actual_weight_items && is_array($order->actual_weight_items)) {
            $message .= "*Items Aktual:*\n";
            foreach ($order->actual_weight_items as $item) {
                $itemName = $item['item_name'] ?? '-';
                $quantity = $item['quantity'] ?? 0;
                $unit = $item['unit'] ?? 'kg';
                $subtotal = $item['subtotal'] ?? 0;
                $message .= "• {$itemName}: {$quantity} {$unit} - Rp ".number_format($subtotal, 0, ',', '.')."\n";
            }
            $message .= "\n";
        }

        // Show total comparison
        if ($order->actual_total_amount) {
            $message .= '💰 *Total Estimasi:* Rp '.number_format($order->subtotal ?? 0, 0, ',', '.')."\n";
            $message .= '💰 *Total Aktual:* Rp '.number_format($order->actual_total_amount, 0, ',', '.')."\n\n";

            $difference = $order->actual_total_amount - ($order->subtotal ?? 0);
            if ($difference > 0) {
                $message .= '📈 Selisih: +Rp '.number_format($difference, 0, ',', '.')."\n\n";
            } elseif ($difference < 0) {
                $message .= '📉 Selisih: -Rp '.number_format(abs($difference), 0, ',', '.')."\n\n";
            }
        }

        if ($order->proof_video_url) {
            $message .= "📹 Video bukti penimbangan tersedia di aplikasi.\n\n";
        }

        $message .= 'Terima kasih atas kepercayaan Anda! 🙏';

        return $this->sendMessage($order->customer_phone, $message);
    }
}
