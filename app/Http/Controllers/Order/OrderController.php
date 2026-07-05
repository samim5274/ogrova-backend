<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use App\Http\Requests\ConfirmOrderRequest;
use App\Models\User;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Services\PointService;
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
    //         'd_payment_method' => [
    //             'nullable',
    //             Rule::requiredIf(fn () => $request->payment_method === 'cod'),
    //             Rule::in(['mobile','bank']),
    //         ],

    //         // Mobile Banking
    //         'mobile_number' => [
    //             'nullable',
    //             Rule::requiredIf(fn () =>
    //                 $request->payment_method === 'cod' &&
    //                 $request->d_payment_method === 'mobile'
    //             ),
    //         ],
    //         'transaction_id' => [
    //             'nullable',
    //             Rule::requiredIf(fn () =>
    //                 $request->payment_method === 'cod' &&
    //                 $request->d_payment_method === 'mobile'
    //             ),
    //         ],

    //         // Bank Transfer
    //         'bank_name' => [
    //             'nullable',
    //             Rule::requiredIf(fn () =>
    //                 $request->payment_method === 'cod' &&
    //                 $request->d_payment_method === 'bank'
    //             ),
    //         ],
    //         'account_number' => 'nullable|required_if:d_payment_method,bank|string|max:100',
    //         'account_holder_name' => 'nullable|required_if:d_payment_method,bank|string|max:255',
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
    //                 'payment_method'        => $request->d_payment_method,
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

        $address = CustomerAddress::query()
            ->whereKey($validated['address_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping address not found.',
            ], 404);
        }

        $cartItems = Cart::with('product')->where('reg', $reg)->where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Cart items is empty.",
            ]);
        }

        if (Order::where(['reg'=>$reg,'user_id'=>$user->id])->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Order already created.",
            ]);
        }

        $amount     = $cartItems->sum(fn($item) => $item->price * $item->quantity);
        $point      = $cartItems->sum(fn($item) => $item->point * $item->quantity);
        $discount   = $cartItems->sum(fn($item) => $item->discount * $item->quantity);

        DB::beginTransaction();

        try {
            // $transactionId = $this->generateTransactionId();

            $order = Order::create([
                'reg'                       => $reg,
                'date'                      => now()->toDateString(),
                'user_id'                   => $user->id,

                'amount'                    => $amount,
                'discount'                  => $discount,
                'payable_amount'            => max(0,$amount-$discount),
                'currency'                  => 'BDT',
                'point'                     => (int) $point,

                'payment_method'            => $request->payment_method === 'advance' ? 'online' : 'cod',

                'payment_status'            => $request->payment_method === 'advance' ? 'Paid': 'Pending',
                'paid_at'                   => $request->payment_method === 'advance' ? now() : null,

                'status'                    => 'Pending',

                'contact_name'              => $address->recipient_name,
                'contact_number'            => $address->phone,
                'contact_email'             => $user->email,

                'division_id'               => $address->division_id,
                'district_id'               => $address->district_id,
                'upazila_id'                => $address->upazila_id,
                'police_station_id'         => $address->police_station_id,
                'postal_code'               => $address->postal_code,

                'shipping_address'          => $address->address,
                'remarks'                   => $request->remarks ?? "N/A",
            ]);

            if ($request->boolean('save_info')) {

                $user->update([
                    'present_address' => $address->address,
                ]);
            }

            if ($request->payment_method === 'advance') {

                $transactionId = 'TXN-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8));

                // Transaction Table
                Transaction::create([
                    'transaction_id'      => $request->transaction_id,
                    'user_id'             => $user->id,
                    'amount'              => $order->payable_amount,
                    'charge'              => 0,
                    'net_amount'          => $order->payable_amount,
                    'payment_method'      => 'online', // bkash/nagad/sslcommerz

                    'status'              => 'paid',

                    'is_confirm'          => true,

                    'requested_at'        => now(),
                    'processed_at'        => now(),
                ]);

                // Order Payment Table
                OrderPayment::create([

                    'order_id'                  => $order->id,
                    'user_id'                   => $user->id,

                    'payment_method'            => OrderPayment::METHOD_SSLCOMMERZ,
                    // METHOD_MOBILE_BANKING

                    'gateway'                   => 'SSLCommerz',

                    'transaction_id'            => $request->transaction_id,

                    'amount'                    => $order->payable_amount,

                    'currency'                  => 'BDT',

                    'status'                    => OrderPayment::STATUS_SUCCESS,

                    'paid_at'                   => now(),

                    'remarks'                   => 'Advance payment',
                ]);
            }

            DB::commit();

            return response()->json([
                'success'=>true,
                'message'=>'Order placed successfully.',
                'data'=>[
                    'order_id'=>$order->id,
                    'order_number'=>$order->reg,
                    'payment_status'=>$order->payment_status
                ]
            ],201);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Order confirmation failed',[
                'user'=>$user->id,
                'reg'=>$reg,
                'error'=>$e->getMessage(),
                'trace'=>$e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Order created.",
        ]);

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
            $order = Order::with('user')
                ->where('reg', $reg)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order fetched successfully.',
                'data' => $order,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order. Please try again later.',
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
}
