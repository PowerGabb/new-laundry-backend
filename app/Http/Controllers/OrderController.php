<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateActualWeightRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Branch;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(protected WhatsAppService $whatsAppService) {}

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

            // Calculate pricing based on items_detail if provided
            if ($request->has('items_detail') && is_array($request->items_detail) && count($request->items_detail) > 0) {
                // Item-based pricing (NEW system)
                $subtotal = 0;
                foreach ($request->items_detail as $item) {
                    $subtotal += $item['subtotal'] ?? 0;
                }
                $pricePerKg = 0; // Not used in item-based
            } else {
                // Legacy weight-based pricing (fallback for old orders)
                $pricePerKg = $branch->price_per_kg;
                $subtotal = $request->estimated_weight * $pricePerKg;
            }

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
                'estimated_weight' => $request->estimated_weight ?? 0,
                'items_detail' => $request->items_detail, // Store selected items
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

            // Send WhatsApp notification to branch
            $order->load(['branch', 'user']);
            $this->whatsAppService->notifyBranchNewOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => new OrderResource($order),
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

    /**
     * Update order status (admin only)
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        // Get the branch owner's branch
        $userBranch = auth()->user()->branches()->first();

        // Check if user owns a branch
        if (! $userBranch) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki cabang',
            ], 403);
        }

        // Check if order belongs to this branch
        if ($order->branch_id !== $userBranch->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan di cabang Anda',
            ], 403);
        }

        // Update order status
        $order->update([
            'order_status' => $request->order_status,
        ]);

        // Add notes to order if provided
        if ($request->notes) {
            $order->update([
                'notes' => $request->notes,
            ]);
        }

        // Send WhatsApp notification to customer
        $order->load(['branch', 'user', 'pickupStaff', 'deliveryStaff']);
        $this->whatsAppService->notifyCustomerStatusUpdate($order);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diupdate',
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Get branch statistics for admin dashboard
     */
    public function getBranchStats(Request $request): JsonResponse
    {
        // Get the branch owner's branch
        $userBranch = auth()->user()->branches()->first();

        // Check if user owns a branch
        if (! $userBranch) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki cabang',
            ], 403);
        }

        $branchId = $userBranch->id;

        // Total orders
        $totalOrders = Order::where('branch_id', $branchId)->count();

        // Orders by status
        $ordersByStatus = Order::where('branch_id', $branchId)
            ->select('order_status', DB::raw('count(*) as count'))
            ->groupBy('order_status')
            ->get()
            ->pluck('count', 'order_status');

        // Revenue calculations
        $revenueToday = Order::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $revenueWeek = Order::where('branch_id', $branchId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $revenueMonth = Order::where('branch_id', $branchId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // Recent orders (last 10)
        $recentOrders = Order::where('branch_id', $branchId)
            ->with(['user', 'branch'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'orders_by_status' => [
                    'pending' => $ordersByStatus['pending'] ?? 0,
                    'processing' => $ordersByStatus['processing'] ?? 0,
                    'washing' => $ordersByStatus['washing'] ?? 0,
                    'ready' => $ordersByStatus['ready'] ?? 0,
                    'picked_up' => $ordersByStatus['picked_up'] ?? 0,
                    'delivering' => $ordersByStatus['delivering'] ?? 0,
                    'completed' => $ordersByStatus['completed'] ?? 0,
                    'cancelled' => $ordersByStatus['cancelled'] ?? 0,
                ],
                'revenue' => [
                    'today' => (int) $revenueToday,
                    'week' => (int) $revenueWeek,
                    'month' => (int) $revenueMonth,
                ],
                'recent_orders' => OrderResource::collection($recentOrders),
            ],
        ]);
    }

    /**
     * Update actual weight and items with video proof (admin only)
     */
    public function updateActualWeight(UpdateActualWeightRequest $request, Order $order): JsonResponse
    {
        // Get the branch owner's branch
        $userBranch = auth()->user()->branches()->first();

        // Check if user owns a branch
        if (! $userBranch) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki cabang',
            ], 403);
        }

        // Check if order belongs to this branch
        if ($order->branch_id !== $userBranch->id) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan di cabang Anda',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Calculate actual total from items
            $actualTotalAmount = 0;
            foreach ($request->actual_weight_items as $item) {
                $actualTotalAmount += $item['subtotal'] ?? 0;
            }

            // Add delivery fee if exists
            $actualTotalAmount += $order->delivery_fee ?? 0;

            // Update order with actual weight data
            $order->update([
                'actual_weight_items' => $request->actual_weight_items,
                'actual_total_amount' => $actualTotalAmount,
                'actual_weight' => $request->actual_weight,
                'proof_video_url' => $request->proof_video_url,
                'actual_weight_recorded_at' => now(),
                'notes' => $request->notes ?? $order->notes,
            ]);

            DB::commit();

            // Send WhatsApp notification to customer about actual weight update
            $order->load(['branch', 'user']);
            $this->whatsAppService->notifyCustomerActualWeightUpdate($order);

            return response()->json([
                'success' => true,
                'message' => 'Berat actual berhasil diperbarui',
                'data' => new OrderResource($order->fresh()),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui berat actual',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
