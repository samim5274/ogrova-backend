<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Models\User;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\RegGenerator;
use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\CustomerOrderRequest;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Models\PointTransaction;
use App\Services\PointService;
use App\Mail\OrderMail;
use App\Models\DeliveryChargePayment;
use App\Models\Division;
use App\Models\District;
use App\Models\Upazila;
use App\Models\PoliceStation;
use App\Models\ShippingZone;
use App\Models\CustomerAddress;
use App\Models\Transaction;
use App\Models\OrderPayment;
use App\Models\Coupon;
use App\Models\CouponUsage;

class AdminCartController extends Controller
{
    public function index(){

        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized user',
            ], 401);
        }

        $reg = RegGenerator::generateOrderReg($userId);

        $items = Cart::with(['product.images','variant','user'])
            ->where('user_id', $userId)
            ->where('reg', $reg)
            ->get();

        return response()->json([
            'message' => 'Cart items',
            'reg' => $reg,
            'data' => $items
        ], 200);
    }

    public function adminAddToCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $reg = RegGenerator::generateOrderReg($user->id);
        if (!$reg) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate cart session.'.$user->id
            ], 500);
        }

        try{
            return DB::transaction(function () use ($data, $user, $reg) {

                $product = Product::lockForUpdate()->findOrFail($data['product_id']);

                // ======================
                // Variant handling
                // ======================
                $variant = null;

                if (!empty($data['variant_id'])) {
                    $variant = ProductVariant::lockForUpdate()
                        ->where('product_id', $product->id)
                        ->findOrFail($data['variant_id']);
                }

                // ======================
                // Stock source
                // ======================
                $source = $variant ?: $product;

                if ($variant) {
                    $basePrice = (float) $variant->price;
                    $discountAmount = (float) ($variant->discount ?? 0);
                } else {
                    $basePrice = (float) $product->price;
                    $discountAmount = (float) ($product->discount ?? 0);
                }
                $finalPrice = max(0, $basePrice - $discountAmount);

                // ======================
                // Cart item find
                // ======================
                $query = Cart::where('reg', $reg)
                    ->where('product_id', $product->id);

                if ($variant) {
                    $query->where('variant_id', $variant->id);
                } else {
                    $query->whereNull('variant_id');
                }

                $cartItem = $query->first();

                // ======================
                // Quantity logic
                // ======================
                $requestedQty = 1;
                $currentQty = $cartItem->quantity ?? 0;
                $newQty = $currentQty + $requestedQty;

                // ======================
                // Save cart
                // ======================
                if ($cartItem) {
                    $cartItem->update([
                        'quantity'          => $newQty,
                        'price'             => $basePrice,
                        'discount'          => $discountAmount,
                        'payable_amount'    => $finalPrice,
                    ]);
                } else {
                    $cartItem = Cart::create([
                        'reg'               => $reg,
                        'user_id'           => $user->id,
                        'product_id'        => $product->id,
                        'variant_id'        => $variant?->id,
                        'quantity'          => $requestedQty,
                        'price'             => $basePrice,
                        'discount'          => $discountAmount,
                        'payable_amount'    => $finalPrice,
                        'point'             => $product->point,
                    ]);
                }

                // ======================
                // RESPONSE (OUTSIDE EXCEPTION FLOW STYLE)
                // ======================
                return response()->json([
                    'success' => true,
                    'message' => 'Product added to cart successfully.',
                    'data' => [
                        'cart_id'    => $cartItem->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant?->id,
                        'quantity'   => $cartItem->quantity,
                        'price'      => (float) $finalPrice,
                        'total'      => (float) ($finalPrice * $cartItem->quantity)
                    ]
                ], 201);

            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function updateQty(Request $request, $reg, $product_id, $variant_id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cartItem = Cart::where('reg', $reg)
            ->where('product_id', $product_id)
            ->when(
                !empty($variant_id) && $variant_id !== 'null',
                fn ($query) => $query->where('variant_id', $variant_id),
                fn ($query) => $query->whereNull('variant_id')
            )
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Qty updated successfully',
            'quantity' => $cartItem->quantity,
        ]);
    }

    public function removeToCart(Request $request, $cart_id, $reg, $product_id, $variant_id){

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        try{
            if (!$reg || !$product_id || !$variant_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request data'
                ], 422);
            }

            $cartItem = Cart::where('id', $cart_id)
                ->where('user_id', $user->id)
                ->where('reg', $reg)
                ->where('product_id', $product_id)
                ->when(
                    !empty($variant_id) && $variant_id !== 'null',
                    fn ($query) => $query->where('variant_id', $variant_id),
                    fn ($query) => $query->whereNull('variant_id'),
                )
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found'
                ], 404);
            }

            $cartItem->delete();

            $remaining = Cart::where('user_id', $user->id)
                ->where('reg', $reg)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully',
                'remaining_items' => $remaining
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('Cart Remove Error', [
                'user_id' => $user->id,
                'cart_id' => $cart_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function getCartItem($reg)
    {
        try {
            $items = Cart::with(['product.images','variant','user'])
                        ->where('reg', $reg)->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No cart items found.',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cart items fetched successfully.',
                'reg' => $reg,
                'data' => $items
            ], 200);

        } catch (\Throwable $e) {

            \Log::error('Cart fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching cart items.',
            ], 500);
        }
    }

    public function confirmOrder(CustomerOrderRequest $request, $reg)
    {
        $validated = $request->validated();

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        try {

            $response = DB::transaction(function () use ($validated, $user, $reg, $request) {

                // ----------------------------------------------------------------
                // STEP 1: Duplicate order guard (same reg + user shouldn't create twice)
                // ----------------------------------------------------------------
                if (Order::where([
                    'reg'     => $reg,
                    'user_id' => $user->id,
                ])->lockForUpdate()->exists()) {

                    throw ValidationException::withMessages([
                        'order' => ['Order already created.'],
                    ]);
                }

                // ----------------------------------------------------------------
                // STEP 2: Cart Section — lock cart rows to prevent race condition
                // ----------------------------------------------------------------
                $cartItems = Cart::where('reg', $reg)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->get();

                if ($cartItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => ['Cart is empty.'],
                    ]);
                }

                // ----------------------------------------------------------------
                // STEP 3: Shipping Address — FIX: no CustomerAddress lookup.
                // ----------------------------------------------------------------
                $recipientName   = $validated['recipient_name'];
                $recipientPhone  = $validated['phone'];
                $divisionId      = $validated['division_id'];
                $districtId      = $validated['district_id'];
                $upazilaId       = $validated['upazila_id'];
                $policeStationId = $validated['police_station_id'] ?? null;
                $postalCode      = $validated['postal_code'] ?? null;
                $shippingAddress = $validated['address'];

                // ----------------------------------------------------------------
                // STEP 4: Shipping Charge
                // ----------------------------------------------------------------
                $shippingZone = ShippingZone::query()
                    ->where('district_id', $districtId)
                    ->where('is_active', true)
                    ->first();

                if (!$shippingZone) {
                    throw ValidationException::withMessages([
                        'district_id' => ['Delivery is not available in this area.'],
                    ]);
                }

                $deliveryCharge = round($shippingZone->delivery_charge, 2);
                $codCharge = $validated['payment_method'] === 'cod'
                    ? round($shippingZone->cod_charge, 2)
                    : 0;

                // ----------------------------------------------------------------
                // STEP 5: Cart totals (server-side calculation — client কে trust করা হয় না)
                // ----------------------------------------------------------------
                $amount   = round($cartItems->sum(fn ($item) => $item->price * $item->quantity), 2);
                $point    = (int) $cartItems->sum(fn ($item) => $item->point * $item->quantity);
                $discount = round($cartItems->sum(fn ($item) => $item->discount * $item->quantity), 2);

                $netAmount = round(max(0, $amount - $discount), 2);

                // ----------------------------------------------------------------
                // STEP 6: Coupon Section
                // ----------------------------------------------------------------
                $coupon = null;
                $couponDiscount = 0;

                if (!empty($validated['coupon'])) {

                    $coupon = Coupon::lockForUpdate()
                        ->where('code', $validated['coupon'])
                        ->where('is_active', true)
                        ->first();

                    if (!$coupon) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Invalid coupon code.'],
                        ]);
                    }

                    if ($coupon->start_date && now()->lt($coupon->start_date)) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Coupon is not active yet.'],
                        ]);
                    }

                    if ($coupon->end_date && now()->gt($coupon->end_date)) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Coupon has expired.'],
                        ]);
                    }

                    if (
                        !is_null($coupon->usage_limit) &&
                        $coupon->used_count >= $coupon->usage_limit
                    ) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Coupon usage limit exceeded.'],
                        ]);
                    }

                    if (
                        CouponUsage::where('coupon_id', $coupon->id)
                            ->where('user_id', $user->id)
                            ->exists()
                    ) {
                        throw ValidationException::withMessages([
                            'coupon' => ['You have already used this coupon.'],
                        ]);
                    }

                    if (!is_null($coupon->minimum_amount) && $netAmount < $coupon->minimum_amount) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Minimum order amount is ' . $coupon->minimum_amount],
                        ]);
                    }

                    if ($coupon->discount_type === 'percent') {
                        $couponDiscount = ($netAmount * $coupon->discount) / 100;

                        if (!empty($coupon->maximum_discount) && $couponDiscount > $coupon->maximum_discount) {
                            $couponDiscount = $coupon->maximum_discount;
                        }
                    } else {
                        $couponDiscount = $coupon->discount;
                    }

                    $couponDiscount = round(min($couponDiscount, $netAmount), 2);
                }

                $totalDiscount = round($discount + $couponDiscount, 2);
                $payableAmount = round(max(0, ($amount - $totalDiscount) + $deliveryCharge + $codCharge), 2);

                // ----------------------------------------------------------------
                // STEP 7: Create Order
                // ----------------------------------------------------------------
                $order = Order::create([
                    'reg'   => $reg,
                    'slug'  => Str::slug($reg . '-' . now()->format('YmdHis')), // unique slug generate
                    'date'  => now()->toDateString(),
                    'user_id' => $user->id,

                    'coupon_id'       => $coupon?->id,
                    'coupon_code'     => $coupon?->code,

                    'amount'          => $amount,
                    'coupon_discount' => $couponDiscount,
                    'shipping_charge' => $deliveryCharge,
                    'discount'        => $totalDiscount,
                    'payable_amount'  => $payableAmount,
                    'paid_amount'     => 0,
                    'due_amount'      => $payableAmount,
                    'currency'        => Order::CURRENCY_BDT,
                    'point'           => $point,

                    'payment_method'  => $validated['payment_method'],
                    'payment_status'  => Order::PAYMENT_PENDING,
                    'paid_at'         => null,
                    'submitted_at'    => $validated['payment_method'] === 'advance' ? now() : null,

                    'status' => Order::STATUS_PENDING,

                    'contact_name'   => $recipientName,
                    'contact_number' => $recipientPhone,
                    'contact_email'  => $user->email,

                    'division_id'       => $divisionId,
                    'district_id'       => $districtId,
                    'upazila_id'        => $upazilaId,
                    'police_station_id' => $policeStationId,
                    'postal_code'       => $postalCode,

                    'shipping_address' => $shippingAddress,
                    'remarks'          => $validated['remarks'] ?? null,

                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // ----------------------------------------------------------------
                // STEP 8: Order Payment (Product Advance Payment)
                // ----------------------------------------------------------------
                if ($validated['payment_method'] === 'advance') {

                    OrderPayment::create([
                        'order_id' => $order->id,
                        'user_id'  => $user->id,

                        'payment_method' => $validated['trans_payment_method'] === 'mobile'
                            ? OrderPayment::METHOD_MOBILE_BANKING
                            : OrderPayment::METHOD_BANK_TRANSFER,

                        'provider'     => OrderPayment::PROVIDER_MANUAL,
                        'channel'      => OrderPayment::CHANNEL_OFFLINE,
                        'payment_type' => OrderPayment::TYPE_PAYMENT,

                        'transaction_id'       => $validated['transaction_id'] ?? null,
                        'bank_name'            => $validated['bank_name'] ?? null,
                        'account_number'       => $validated['account_number'] ?? null,
                        'account_holder_name'  => $validated['account_holder_name'] ?? null,
                        'sender_mobile'        => $validated['sender_mobile'] ?? null,
                        'sender_name'          => $validated['sender_name'] ?? null,

                        'amount'      => $order->payable_amount,
                        'gateway_fee' => 0,
                        'net_amount'  => $order->payable_amount,
                        'currency'    => OrderPayment::CURRENCY_BDT,

                        'status'  => OrderPayment::STATUS_PENDING,
                        'paid_at' => null,

                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'receipt_no' => null,

                        'remarks' => 'Advance payment submitted. Waiting for admin verification.',
                    ]);
                }

                // ----------------------------------------------------------------
                // STEP 9: Coupon Usage Log
                // ----------------------------------------------------------------
                if ($coupon) {
                    CouponUsage::create([
                        'coupon_id'       => $coupon->id,
                        'user_id'         => $user->id,
                        'order_id'        => $order->id,
                        'discount_amount' => $couponDiscount,
                        'order_amount'    => $amount,
                        'coupon_code'     => $coupon->code,
                    ]);

                    $coupon->increment('used_count');
                }

                // ----------------------------------------------------------------
                // STEP 10: Delivery Charge Payment (checkbox controlled)
                // ----------------------------------------------------------------
                $isDeliveryChargePayment = filter_var(
                    $validated['is_delivery_charge_payment'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );

                if ($isDeliveryChargePayment) {

                    $deliveryMethod = $validated['delivery_trans_payment_method']; // 'mobile' | 'bank'

                    DeliveryChargePayment::create([
                        'order_id' => $order->id,

                        'payment_date'   => now(),
                        'payment_method' => $deliveryMethod, // migration enum: bank | mobile | sslcommerz | cash

                        'amount'   => $deliveryCharge,
                        'currency' => 'BDT',

                        // bank_name column এ mobile banking provider (Bkash/Nagad/Rocket)
                        'bank_name' => $validated['delivery_bank_name'] ?? null,

                        'branch_name' => null, // frontend এ এই field নেই

                        // Method
                        'account_number' => $deliveryMethod === 'bank'
                            ? ($validated['delivery_account_number'] ?? null)
                            : null,

                        'mobile_number' => $deliveryMethod === 'mobile'
                            ? ($validated['delivery_account_number'] ?? null)
                            : null,

                        'account_holder_name' => $validated['delivery_account_holder_name'] ?? null,
                        'transaction_id'      => $validated['delivery_transaction_id'] ?? null,
                        'reference_no'        => null,

                        'payment_status' => 'pending', // admin manually verify
                        'paid_by'        => $user->id,

                        'notes' => 'Delivery charge submitted by customer. Waiting for admin verification.',
                    ]);
                }

                // ----------------------------------------------------------------
                // STEP 11: Mail (Optional)
                // ----------------------------------------------------------------
                // Mail::to($user->email)->send(new OrderMail($order));

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully.',
                    'data' => [
                        'order_id'       => $order->id,
                        'order_number'   => $order->reg,
                        'payment_status' => $order->payment_status,
                        'payable_amount' => $order->payable_amount,
                    ],
                ], 201);
            });

            return $response;

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {

            Log::error('Order confirmation failed', [
                'user_id' => $user?->id,
                'reg'     => $reg,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->isProduction()
                    ? 'Something went wrong. Please try again.'
                    : $e->getMessage(),
            ], 500);
        }
    }

}
