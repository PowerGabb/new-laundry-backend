<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with(['branch', 'user', 'pickupStaff', 'deliveryStaff'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get branch info
            $branch = Branch::findOrFail($request->branch_id);

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Determine if pickup is free or using courier
            $isPickupFree = $request->pickup_method === 'free_pickup';
            $isPickupCourier = in_array($request->pickup_method, ['gojek', 'grab']);

            // Calculate pricing
            $pricePerKg = $branch->price_per_kg;
            $subtotal = $request->estimated_weight * $pricePerKg;
            $pickupShippingFee = $isPickupFree ? 0 : ($request->shipping_fee ?? 0);
            $totalAmount = $subtotal + $pickupShippingFee;

            // Create order data
            $orderData = [
                'user_id' => auth()->id(),
                'branch_id' => $request->branch_id,
                'order_number' => $orderNumber,
                'order_status' => 'pending',
                'payment_status' => 'unpaid', // Always unpaid for now (bayar di akhir)
                'payment_method' => null, // Will be set later when customer choose payment

                // Pricing
                'total_amount' => $totalAmount,
                'subtotal' => $subtotal,
                'delivery_fee' => 0, // Delivery fee will be calculated later
                'price_per_kg' => $pricePerKg,

                // Items
                'estimated_weight' => $request->estimated_weight,
                'notes' => $request->notes,
                'special_instructions' => $request->special_instructions,

                // Customer info
                'customer_latitude' => $request->customer_latitude,
                'customer_longitude' => $request->customer_longitude,
                'customer_address' => $request->customer_address,
                'customer_phone' => $request->customer_phone,
                'customer_name' => $request->customer_name ?? auth()->user()->name,

                // Pickup scheduling
                'pickup_scheduled_time' => $request->pickup_scheduled_time,

                // Pickup method
                'is_pickup_free' => $isPickupFree,
                'is_pickup_courier' => $isPickupCourier,
                'pickup_method' => $request->pickup_method,
            ];

            // Add pickup courier info if using courier (gojek/grab)
            if ($isPickupCourier) {
                $orderData = array_merge($orderData, [
                    'pickup_courier_company' => $request->company,
                    'pickup_courier_name' => $request->courier_name,
                    'pickup_courier_code' => $request->courier_code,
                    'pickup_courier_service_name' => $request->courier_service_name,
                    'pickup_courier_service_code' => $request->courier_service_code,
                    'pickup_currency' => $request->currency ?? 'IDR',
                    'pickup_courier_description' => $request->description,
                    'pickup_duration' => $request->duration,
                    'pickup_shipment_duration_range' => $request->shipment_duration_range,
                    'pickup_shipment_duration_unit' => $request->shipment_duration_unit,
                    'pickup_shipping_type' => $request->shipping_type,
                    'pickup_courier_rate' => $request->price,
                    'pickup_shipping_fee' => $request->shipping_fee,
                    'pickup_shipping_fee_discount' => $request->shipping_fee_discount ?? 0,
                    'pickup_shipping_fee_surcharge' => $request->shipping_fee_surcharge ?? 0,
                ]);
            }

            $order = Order::create($orderData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => new OrderResource($order->load(['branch', 'user'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke order ini',
            ], 403);
        }

        $order->load(['branch', 'user', 'pickupStaff', 'deliveryStaff']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Choose delivery and payment method when order is ready.
     */
    public function chooseDeliveryAndPayment(Request $request, Order $order): JsonResponse
    {
        // Check if user owns the order
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke order ini',
            ], 403);
        }

        // Check if order is ready
        if ($order->order_status !== 'ready') {
            return response()->json([
                'success' => false,
                'message' => 'Order belum ready untuk dipilih metode pengambilan',
            ], 400);
        }

        $request->validate([
            'delivery_method' => 'required|in:self_pickup,free_delivery,gojek,grab',
            'payment_method' => 'required|in:cash,online',
        ]);

        try {
            DB::beginTransaction();

            $deliveryMethod = $request->delivery_method;
            $paymentMethod = $request->payment_method;

            // Determine delivery flags
            $isDeliveryFree = $deliveryMethod === 'free_delivery';
            $isDeliveryCourier = in_array($deliveryMethod, ['gojek', 'grab']);
            $isSelfPickup = $deliveryMethod === 'self_pickup';

            $updateData = [
                'delivery_method' => $deliveryMethod,
                'is_delivery_free' => $isDeliveryFree,
                'is_delivery_courier' => $isDeliveryCourier,
            ];

            // If cash payment, mark as paid immediately
            if ($paymentMethod === 'cash') {
                $updateData['payment_method'] = 'cash';
                $updateData['payment_status'] = 'paid';
                $updateData['paid_at'] = now();

                // Update order status based on delivery method
                if ($isSelfPickup) {
                    $updateData['order_status'] = 'completed'; // User will pick up directly
                } else {
                    $updateData['order_status'] = 'delivering'; // Ready to be delivered
                }
            } else {
                // Online payment - will be paid via Midtrans
                $updateData['payment_method'] = 'midtrans';
                $updateData['payment_status'] = 'pending';
            }

            $order->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pilihan berhasil disimpan',
                'data' => new OrderResource($order->load(['branch', 'user'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pilihan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate unique order number.
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        return "ORD-{$date}-{$random}";
    }

    /**
     * Get order statistics for authenticated user.
     */
    public function getStats(): JsonResponse
    {
        $userId = auth()->id();

        $totalOrders = Order::where('user_id', $userId)->count();
        $completedOrders = Order::where('user_id', $userId)
            ->where('order_status', 'completed')
            ->count();
        $activeOrders = Order::where('user_id', $userId)
            ->whereNotIn('order_status', ['completed', 'cancelled'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'active_orders' => $activeOrders,
            ],
        ]);
    }
}
