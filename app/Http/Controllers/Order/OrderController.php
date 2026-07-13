<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Models\User;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Services\PointService;
use App\Mail\OrderMail;
use App\Models\Cart;
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

class OrderController extends Controller
{
    protected $pointService;

    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    private function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(10));
        } while (
            Transaction::where('transaction_id', $transactionId)->exists()
        );

        return $transactionId;
    }

    public function index(){
        try{
            $orders = Order::with('user')
                ->where('status', '!=' , 'Delivered')
                ->where('status', '!=' , 'Cancelled')
                ->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Orders fetched successfully.',
                'data' => $orders,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders. Please try again later.',
            ], 500);
        }
    }

    // COD advance order confirm function
    // public function confirmOrderAdvanceDelivery(Request $request, $reg)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name'           => 'required|string|max:255',
    //         'phone'          => 'required|string|max:20',
    //         'email'          => 'required|email|max:255',
    //         'address'        => 'required|string|max:1000',
    //         'remarks'        => 'nullable|string|max:1000',

    //         'same_address'   => 'nullable|boolean', // if true, use user's present address
    //         'save_info'      => 'nullable|boolean',

    //         // Main Payment Method
    //         'payment_method' => 'required|in:cod,advance',

    //         // Delivery Charge Payment Method
    //         'trans_payment_method' => [
    //             'nullable',
    //             Rule::requiredIf(fn () => $request->payment_method === 'cod'),
    //             Rule::in(['mobile','bank']),
    //         ],

    //         // Mobile Banking
    //         'mobile_number' => [
    //             'nullable',
    //             Rule::requiredIf(fn () =>
    //                 $request->payment_method === 'cod' &&
    //                 $request->trans_payment_method === 'mobile'
    //             ),
    //         ],
    //         'transaction_id' => [
    //             'nullable',
    //             Rule::requiredIf(fn () =>
    //                 $request->payment_method === 'cod' &&
    //                 $request->trans_payment_method === 'mobile'
    //             ),
    //         ],

    //         // Bank Transfer
    //         'bank_name' => [
    //             'nullable',
    //             Rule::requiredIf(fn () =>
    //                 $request->payment_method === 'cod' &&
    //                 $request->trans_payment_method === 'bank'
    //             ),
    //         ],
    //         'account_number' => 'nullable|required_if:trans_payment_method,bank|string|max:100',
    //         'account_holder_name' => 'nullable|required_if:trans_payment_method,bank|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $validator->errors()->first(),
    //             'errors'  => $validator->errors(),
    //         ], 422);
    //     }

    //     $user = auth()->user();
    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized user',
    //         ], 401);
    //     }

    //     $cartItems = Cart::with('product')->where('reg', $reg)->where('user_id', $user->id)->get();
    //     if ($cartItems->isEmpty()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Cart items is empty.",
    //         ]);
    //     }

    //     if (Order::where(['reg'=>$reg,'user_id'=>$user->id])->exists()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Order already created.",
    //         ]);
    //     }

    //     $amount     = $cartItems->sum(fn($item) => $item->price * $item->quantity);
    //     $point      = $cartItems->sum(fn($item) => $item->point * $item->quantity);
    //     $discount   = $cartItems->sum(fn($item) => $item->discount * $item->quantity);

    //     DB::beginTransaction();

    //     try {
    //         $transactionId = $this->generateTransactionId();

    //         $order = Order::create([
    //             'reg'                       => $reg,
    //             'date'                      => now()->toDateString(),
    //             'user_id'                   => $user->id,

    //             'amount'                    => $amount,
    //             'discount'                  => $discount,
    //             'payable_amount'            => max(0,$amount-$discount),
    //             'currency'                  => 'BDT',
    //             'point'                     => (int) $point,

    //             'payment_method'            => $request->payment_method === 'advance' ? 'Advance' : 'Cash On Delivery',

    //             'transaction_id'            => $transactionId,
    //             'payment_status'            => $request->payment_method === 'advance' ? 'Paid': 'Pending',
    //             'paid_at'                   => $request->payment_method === 'advance' ? now() : null,

    //             'status'                    => 'Pending',

    //             'contact_name'              => $request->name,
    //             'contact_number'            => $request->phone,
    //             'contact_email'             => $request->email ?: $user->email,
    //             'shipping_address'          => $request->address,
    //             'remarks'                   => $request->remarks ?? "N/A",
    //         ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Delivery Charge Payment
    //         |--------------------------------------------------------------------------
    //         */
    //         if ($request->payment_method === 'cod') {

    //             DeliveryChargePayment::create([

    //                 'order_id'              => $order->id,
    //                 'payment_date'          => now(),
    //                 'payment_method'        => $request->trans_payment_method,
    //                 'amount'                => config('app.delivery_charge', 0), // delivery charge amount
    //                 'currency'              => 'BDT',
    //                 'bank_name'             => $request->bank_name,
    //                 'account_number'        => $request->account_number,
    //                 'account_holder_name'   => $request->account_holder_name,
    //                 'mobile_number'         => $request->mobile_number,
    //                 'transaction_id'        => $request->transaction_id,
    //                 'payment_status'        => 'pending',
    //                 'paid_by'               => $user->id,
    //                 'notes'                 => 'Delivery charge submitted by customer.',
    //             ]);
    //         }

    //         if ($request->boolean('save_info')) {

    //             $user->update([
    //                 'present_address' => $request->address,
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success'=>true,
    //             'message'=>'Order placed successfully.',
    //             'data'=>[
    //                 'order_id'=>$order->id,
    //                 'order_number'=>$order->reg,
    //                 'payment_status'=>$order->payment_status
    //             ]
    //         ],201);

    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         Log::error('Order confirmation failed',[
    //             'user'=>$user->id,
    //             'reg'=>$reg,
    //             'error'=>$e->getMessage(),
    //             'trace'=>$e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //             'file'    => $e->getFile(),
    //             'line'    => $e->getLine(),
    //         ], 500);
    //     }

    // }

    public function confirmOrder(ConfirmOrderRequest $request, $reg)
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

                if (Order::where([
                    'reg' => $reg,
                    'user_id' => $user->id
                ])->lockForUpdate()->exists()) {

                    throw ValidationException::withMessages([
                        'order' => ['Order already created.']
                    ]);
                }

                // ---------------- Cart Section ----------------
                $cartItems = Cart::where('reg', $reg)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->get();

                if ($cartItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => ['Cart is empty.']
                    ]);
                }

                // ---------------- Customer Address ----------------
                $address = CustomerAddress::whereKey($validated['address_id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (!$address) {
                    throw ValidationException::withMessages([
                        'address_id' => ['Address not found.']
                    ]);
                }

                // ---------------- Shipping Charge ----------------
                $shippingZone = ShippingZone::query()
                    ->where('district_id', $address->district_id)
                    ->where('is_active', true)
                    ->first();

                if (!$shippingZone) {
                    throw ValidationException::withMessages([
                        'address_id' => ['Delivery is not available in this area.'],
                    ]);
                }

                $deliveryCharge = round($shippingZone->delivery_charge, 2);
                $codCharge = $validated['payment_method'] === 'cod' ? round($shippingZone->cod_charge, 2) : 0;

                $amount   = round($cartItems->sum(fn ($item) => $item->price * $item->quantity), 2);
                $point    = (int) $cartItems->sum(fn ($item) => $item->point * $item->quantity);
                $discount = round($cartItems->sum(fn ($item) => $item->discount * $item->quantity));

                $netAmount = round(max(0, $amount - $discount), 2);



                // ---------------- Coupon Section ----------------
                $coupon = null;
                $couponDiscount = 0;

                if (!empty($validated['coupon'])) {

                    $coupon = Coupon::lockForUpdate()
                        ->where('code', $validated['coupon'])
                        ->where('is_active', true)
                        ->first();

                    if (!$coupon) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Invalid coupon code.']
                        ]);
                    }

                    // Date validation
                    if ($coupon->start_date && now()->lt($coupon->start_date)) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Coupon is not active yet.']
                        ]);
                    }

                    if ($coupon->end_date && now()->gt($coupon->end_date)) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Coupon has expired.']
                        ]);
                    }

                    // Global usage limit
                    if (
                        !is_null($coupon->usage_limit) &&
                        $coupon->used_count >= $coupon->usage_limit
                    ) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Coupon usage limit exceeded.']
                        ]);
                    }

                    // Per user usage
                    if (
                        CouponUsage::where('coupon_id', $coupon->id)
                            ->where('user_id', $user->id)
                            ->exists()
                    ) {
                        throw ValidationException::withMessages([
                            'coupon' => ['You have already used this coupon.']
                        ]);
                    }

                    // Minimum order
                    if (!is_null($coupon->minimum_amount) && $netAmount < $coupon->minimum_amount) {
                        throw ValidationException::withMessages([
                            'coupon' => ['Minimum order amount is ' . $coupon->minimum_amount],
                        ]);
                    }


                    // Discount
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

                // ---------------- Create Order ----------------
                $order = Order::create([
                    'reg'                       => $reg,
                    'date'                      => now()->toDateString(),
                    'user_id'                   => $user->id,

                    'coupon_id'                 => $coupon?->id,
                    'coupon_code'               => $coupon?->code,

                    'amount'                    => $amount,
                    'coupon_discount'           => $couponDiscount,
                    'shipping_charge'           => $deliveryCharge,
                    // 'cod_charge'             => $codCharge,
                    'discount'                  => $totalDiscount,
                    'payable_amount'            => $payableAmount,
                    'currency'                  => Order::CURRENCY_BDT,
                    'point'                     => $point,

                    'payment_method'            => $validated['payment_method'],

                    'payment_status'            => Order::PAYMENT_PENDING, // Manual payment verify
                    'paid_at'                   => null, // $validated['payment_method'] === 'advance' ? now() : null,
                    'submitted_at'              => $validated['payment_method'] === 'advance' ? now() : null,

                    'status'                    => Order::STATUS_PENDING, // Order status

                    'contact_name'              => $address->recipient_name,
                    'contact_number'            => $address->phone,
                    'contact_email'             => $user->email,

                    'division_id'               => $address->division_id,
                    'district_id'               => $address->district_id,
                    'upazila_id'                => $address->upazila_id,
                    'police_station_id'         => $address->police_station_id,
                    'postal_code'               => $address->postal_code,

                    'shipping_address'          => $address->address,
                    'remarks'                   => $validated['remarks'] ?? null,

                    'ip_address'                => $request->ip(),
                    'user_agent'                => $request->userAgent(),
                ]);

                if ($request->boolean('save_info')) {

                    $user->update([
                        'present_address' => $address->address,
                    ]);
                }

                // ---------------- Order Payment (advance payment) ----------------
                if ($validated['payment_method'] === 'advance') {

                    // Order Payment Table
                    OrderPayment::create([
                        'order_id'                  => $order->id,
                        'user_id'                   => $user->id,

                        'payment_method'            => $validated['trans_payment_method'] === 'mobile'
                                                        ? OrderPayment::METHOD_MOBILE_BANKING
                                                        : OrderPayment::METHOD_BANK_TRANSFER,

                        // Manual verification required
                        'provider'                  => OrderPayment::PROVIDER_MANUAL,
                        'channel'                   => OrderPayment::CHANNEL_OFFLINE,
                        'payment_type'              => OrderPayment::TYPE_PAYMENT,

                        // Transaction
                        'transaction_id'            => $validated['transaction_id'],
                        'bank_name'                 => $validated['bank_name'] ?? null,
                        'account_number'            => $validated['account_number'] ?? null,
                        'account_holder_name'       => $validated['account_holder_name'] ?? null,
                        'sender_mobile'             => $validated['sender_mobile'] ?? null,
                        'sender_name'               => $validated['sender_name'] ?? null,

                        // Amount
                        'amount'                    => $order->payable_amount,
                        'gateway_fee'               => 0,
                        'net_amount'                => $order->payable_amount,
                        'currency'                  => OrderPayment::CURRENCY_BDT,

                        // Status
                        'status'                    => OrderPayment::STATUS_PENDING,
                        'paid_at'                   => null,

                        // Security
                        'ip_address'                => request()->ip(),
                        'user_agent'                => request()->userAgent(),
                        'receipt_no'                => null,

                        'remarks'                   => 'Advance payment submitted. Waiting for admin verification.',

                        // for SSLCommerz
                        // 'payment_method' => OrderPayment::METHOD_CARD,
                        // 'provider'       => OrderPayment::PROVIDER_SSLCOMMERZ,
                        // 'channel'        => OrderPayment::CHANNEL_ONLINE,
                        // 'payment_type'   => OrderPayment::TYPE_PAYMENT,

                        // 'gateway_transaction_id' => $sslResponse['tran_id'],
                        // 'gateway_response'        => $sslResponse,

                        // 'status'  => OrderPayment::STATUS_SUCCESS,
                        // 'paid_at' => now(),

                        // for Stripe
                        // 'payment_method' => OrderPayment::METHOD_CARD,
                        // 'provider'       => OrderPayment::PROVIDER_STRIPE,
                        // 'channel'        => OrderPayment::CHANNEL_ONLINE,
                        // 'payment_type'   => OrderPayment::TYPE_PAYMENT,

                        // 'gateway_transaction_id' => $paymentIntent->id,
                        // 'gateway_response'        => $paymentIntent->toArray(),

                        // 'status'  => OrderPayment::STATUS_SUCCESS,
                        // 'paid_at' => now(),
                    ]);
                }

                if ($coupon) {
                    CouponUsage::create([
                        'coupon_id'         => $coupon->id,
                        'user_id'           => $user->id,
                        'order_id'          => $order->id,

                        'discount_amount'   => $couponDiscount,
                        'order_amount'      => $amount,

                        'coupon_code'       => $coupon->code,
                    ]);

                    $coupon->increment('used_count');
                }

                // mailtrap: cf7f17c9f64fdf9c521cd2b5d08e1323
                Mail::to($user->email)->send(new OrderMail($order));

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully.',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->reg,
                        'payment_status' => $order->payment_status,
                        'payable_amount' => $order->payable_amount,
                    ]
                ], 201);
            });

            return $response;

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {

            Log::error('Order confirmation failed', [
                'user_id' => $user?->id,
                'reg' => $reg,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->isProduction()
                    ? 'Something went wrong. Please try again.'
                    : $e->getMessage(),
            ], 500);
        }

    }

    public function statusFilter(){
        try{
            $orders = Order::with('user')->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Orders fetched successfully.',
                'data' => $orders,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders. Please try again later.',
            ], 500);
        }
    }

    public function getOrderDetails($reg){
        try{
            $order = Order::with(['user', 'payment', 'division', 'district', 'upazila', 'policeStation'])
                ->where('reg', $reg)
                ->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                    'data'    => null,
                ], 404);
            }


            $orderPayment = null;

            $orderPayment = OrderPayment::with('verifier:id,name,email')->where('order_id', $order->id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Order fetched successfully.',
                'data' => [
                    'order' => $order,
                    'payment' => $orderPayment,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch order details.', [
                'registration' => $reg,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching order details.',
            ], 500);
        }
    }

    public function updateStatus(Request $request, $reg)
    {
        try {
            $statusInput = trim($request->status);

            $validStatuses = [
                'pending'          => 'Pending',
                'confirmed'        => 'Confirmed',
                'processing'       => 'Processing',
                'picked'           => 'Picked',
                'shipped'          => 'Shipped',
                'out for delivery' => 'Out for Delivery',
                'delivered'        => 'Delivered',
                'cancelled'        => 'Cancelled',
                'failed'           => 'Failed',
                'returned'         => 'Returned',
            ];

            $lowerInput = strtolower($statusInput);
            $normalizedStatus = $validStatuses[$lowerInput] ?? $statusInput;

            $request->merge(['status' => $normalizedStatus]);

            $validated = $request->validate([
                'status' => [
                    'required',
                    'string',
                    'in:' . implode(',', array_values($validStatuses))
                ]
            ]);

            $order = Order::where('reg', $reg)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            if ($order->status === $validated['status']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order status is already ' . $order->status,
                ], 200);
            }

            DB::beginTransaction();

            $currentStatus = $validated['status'];
            $statusKey = strtolower($currentStatus);

            $timestampMapping = [
                'confirmed' => 'confirmed_at',
                'shipped'   => 'shipped_at',
                'delivered' => 'delivered_at',
                'cancelled' => 'cancelled_at',
            ];

            $updateData = ['status' => $currentStatus];

            if (isset($timestampMapping[$statusKey])) {
                $column = $timestampMapping[$statusKey];
                $updateData[$column] = now()->toDateString();
            }

            if ($statusKey === 'delivered') {
                $updateData['paid_at'] = now()->toDateString();
                $updateData['payment_status'] = "Paid";
            }

            $order->update($updateData);

            // if ($statusKey === 'delivered') {
            //     $exists = PointTransaction::where('reference_id', $order->reg)
            //         ->where('source', 'purchase')
            //         ->exists();

            //     if (!$exists) {
            //         PointTransaction::create([
            //             'user_id'        => $order->user_id,
            //             'type'           => 'earn',
            //             'points'         => (int) $order->point,
            //             'bonus_amount'   => 0,
            //             'bonus_status'   => 'credit',
            //             'source'         => 'purchase',
            //             'reference_id'   => $order->reg,
            //             'note'           => 'Points added for delivered order',
            //         ]);
            //     }
            // }

            if ($statusKey === 'delivered') {
                $user = User::find($order->user_id);
                if (!$user) return;

                $exists = PointTransaction::where('reference_id', $order->reg)
                    ->where('source', 'purchase')
                    ->exists();

                if (!$exists) {
                    if ($order->point > 0) {
                        $this->pointService->distributeOrderPoints($user, (int)$order->point, $order->reg);
                    }
                }

                // referral bonus always safe guarded inside service
                $this->pointService->referralBonus($user, $order->reg, (int)$order->point);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order->fresh()
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status selected.',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ORDER STATUS ERROR', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error. Please try again.',
            ], 500);
        }
    }

    public function getCustomerDetails($user_id){
        try{
            $customer = User::where('user_id', $user_id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Customer Details fetched successfully.',
                'data' => $customer,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer details. Please try again later.',
            ], 500);
        }
    }

    public function reportSale()
    {
        try{
            $orders = Order::with('user')->latest()->paginate(20);

            $totalAmount = Order::sum('amount');

            return response()->json([
                'success' => true,
                'message' => 'Orders fetched successfully.',
                'data' => $orders,
                'total_amount' => (float) $totalAmount,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders. Please try again later.',
            ], 500);
        }
    }

    public function reportSaleFilter(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date|date_format:Y-m-d',
            'end_date'   => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        try{

            $startDate = $request->start_date ?? now()->startOfDay()->toDateString();
            $endDate   = $request->end_date ?? now()->endOfDay()->toDateString();

            $query = Order::whereBetween('date', [$startDate, $endDate]);

            $totalAmount = (clone $query)->sum('amount');

            $orders = $query->with('user:id,user_id,name,email')
                ->latest()
                ->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Orders fetched successfully.',
                'data'    => $orders,
                'total_amount' => (float) $totalAmount,
            ], 200);
        } catch (\Throwable $e) {


            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders. Please try again later.',
            ], 500);
        }
    }

    public function verifyPayment(Request $request, int $paymentId): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {

            $orderPayment = OrderPayment::find($paymentId);

            if (! $orderPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found.',
                ], 404);
            }

            if ($orderPayment->status === OrderPayment::STATUS_SUCCESS) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment has already been verified.',
                ], 422);
            }

            $order = Order::find($orderPayment->order_id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            DB::transaction(function () use ($request, $orderPayment, $order, $user)
            {
                $orderPayment->update([
                    'status'      => OrderPayment::STATUS_SUCCESS,
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'remarks'     => 'Advance payment submitted. An admin payment verified.',
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                ]);

                $order->update([
                    'payment_status' => Order::PAYMENT_PAID,
                    'paid_amount'    => $order->payable_amount,
                    'due_amount'     => ($orderPayment->net_amount - $order->payable_amount),
                    'paid_at'        => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data' => [
                    'payment_id' => $orderPayment->id,
                    'order_id'   => $order->id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Payment verification failed.', [
                'payment_id' => $paymentId,
                'user_id'    => $user->id,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to verify payment. Please try again later.',
            ], 500);
        }
    }

    public function confirmPayment(ConfirmPaymentRequest $request, $reg)
    {
        $validated = $request->validated();

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        try
        {
            $payment = DB::transaction(function () use ($validated, $user, $request, $reg) {

                $order = Order::where('reg', $reg)->lockForUpdate()->first();

                if (! $order) {
                    throw ValidationException::withMessages([
                        'order' => ['Order not found.'],
                    ]);
                }

                if ($order->payment_status === Order::PAYMENT_PAID) {
                    throw ValidationException::withMessages([
                        'order' => ['This order has already been paid.'],
                    ]);
                }

                if ($validated['amount'] <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => ['Invalid payment amount.'],
                    ]);
                }

                // Re-check inside the transaction to close the race window
                // between the earlier read and this write.
                if (! empty($validated['transaction_id'])) {
                    $duplicate = OrderPayment::where('transaction_id', $validated['transaction_id'])
                        ->lockForUpdate()
                        ->exists();

                    if ($duplicate) {
                        throw ValidationException::withMessages([
                            'transaction_id' => [
                                'Transaction ID already exists.'
                            ],
                        ]);
                    }
                }

                $paidAmount = $order->payments()->success()->sum('net_amount');
                $remaining = $order->payable_amount - $paidAmount;

                if ($validated['amount'] > $remaining) {
                    throw ValidationException::withMessages([
                        'amount' => ['Payment exceeds due amount.'],
                    ]);
                }

                do {
                    $receiptNo = 'RCPT-' . Str::upper(Str::random(10));
                } while (OrderPayment::where('receipt_no', $receiptNo)->exists());

                $provider = match ($validated['payment_method']) {
                    OrderPayment::METHOD_CASH
                        => OrderPayment::PROVIDER_CASH,
                    OrderPayment::METHOD_BANK_TRANSFER
                        => OrderPayment::PROVIDER_BANK,
                    OrderPayment::METHOD_MOBILE_BANKING
                        => OrderPayment::PROVIDER_MANUAL,
                    OrderPayment::METHOD_CARD
                        => OrderPayment::PROVIDER_STRIPE,
                    OrderPayment::METHOD_PAYPAL
                        => OrderPayment::PROVIDER_PAYPAL,
                    default
                        => OrderPayment::PROVIDER_MANUAL,
                };

                if( $validated['payment_method'] === OrderPayment::METHOD_MOBILE_BANKING)
                {
                    $bankName           = $validated['provider'];
                    $accountNumber      = $validated['sender_mobile'] ?? null;
                    $accountHolderName  = $validated['sender_name'] ?? null;
                } else
                {
                    $bankName           = $validated['bank_name'] ?? null;
                    $accountNumber      = $validated['account_number'] ?? null;
                    $accountHolderName  = $validated['account_holder_name'] ?? null;
                }

                // Order Payment Table
                $orderPayment = OrderPayment::create([
                    'order_id'                  => $order->id,
                    'user_id'                   => $user->id,

                    'payment_method'            => $validated['payment_method'],
                    'provider'                  => $provider,
                    'payment_type'              => OrderPayment::TYPE_PAYMENT,

                    // Manual verification required
                    'channel'                   => OrderPayment::getChannel($validated['payment_method']),

                    // Transaction
                    'transaction_id'            => $validated['transaction_id'] ?? null,
                    'bank_name'                 => $bankName,
                    'account_number'            => $accountNumber,
                    'account_holder_name'       => $accountHolderName,
                    'sender_mobile'             => $validated['sender_mobile'] ?? null,
                    'sender_name'               => $validated['sender_name'] ?? null,

                    // Amount
                    'amount'                    => $validated['amount'],
                    'gateway_fee'               => 0,
                    'net_amount'                => $validated['amount'],
                    'currency'                  => OrderPayment::CURRENCY_BDT,

                    // Status
                    'status'                    => OrderPayment::STATUS_SUCCESS,
                    'paid_at'                   => now(),

                    // Security
                    'ip_address'                => $request->ip(),
                    'user_agent'                => $request->userAgent(),
                    'receipt_no'                => $receiptNo,

                    'verified_by'               => $user->id,
                    'received_by'               => $user->id,
                    'verified_at'               => now(),
                    'remarks'                   => $validated['remarks'] ?? null,
                ]);

                $newPaidAmount = $paidAmount + $validated['amount'];
                $newDueAmount = max($order->payable_amount - $newPaidAmount, 0);
                $paymentStatus = $newDueAmount <= 0
                    ? Order::PAYMENT_PAID
                    : Order::PAYMENT_PARTIAL;

                $order->update([
                    'payment_status' => $paymentStatus,
                    'paid_amount'    => $newPaidAmount,
                    'due_amount'     => $newDueAmount,
                    'paid_at'        => $paymentStatus === Order::PAYMENT_PAID ? now() : null,
                ]);

                return $orderPayment;
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'payment' => $payment,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {

            Log::error('Payment submission failed.', [
                'order_reg'=>$reg,
                'user_id'=>$user->id,
                'amount'=>$validated['amount'] ?? null,
                'payment_method'=>$validated['payment_method'] ?? null,
                'transaction_id'=>$validated['transaction_id'] ?? null,
                'message'=>$e->getMessage(),
                'file'=>$e->getFile(),
                'line'=>$e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->isProduction()
                    ? 'Unable to process payment.'
                    : $e->getMessage(),
            ], 500);
        }
    }
}
