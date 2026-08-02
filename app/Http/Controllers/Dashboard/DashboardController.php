<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderPayment;

class DashboardController extends Controller
{

    public function dashboard()
    {
        try {
            $today = Carbon::today();

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized user.'
                ], 401);
            }

            $orderSummary = [
                // Order Summary
                'total_orders'            => Order::count(),
                'pending_orders'          => Order::pending()->count(),
                'confirmed_orders'        => Order::confirmed()->count(),
                'processing_orders'       => Order::processing()->count(),
                'picked_orders'           => Order::where('status', Order::STATUS_PICKED)->count(),
                'shipped_orders'          => Order::where('status', Order::STATUS_SHIPPED)->count(),
                'out_for_delivery_orders' => Order::where('status', Order::STATUS_OUT_FOR_DELIVERY)->count(),
                'delivered_orders'        => Order::delivered()->count(),
                'cancelled_orders'        => Order::cancelled()->count(),
                'failed_orders'           => Order::where('status', Order::STATUS_FAILED)->count(),
                'returned_orders'         => Order::where('status', Order::STATUS_RETURNED)->count(),

                // Payment Summary
                'pending_payments'        => Order::where('payment_status', Order::PAYMENT_PENDING)->count(),
                'partial_payments'        => Order::where('payment_status', Order::PAYMENT_PARTIAL)->count(),
                'paid_orders'             => Order::paid()->count(),
                'failed_payments'         => Order::where('payment_status', Order::PAYMENT_FAILED)->count(),
                'refunded_orders'         => Order::where('payment_status', Order::PAYMENT_REFUNDED)->count(),

                // Payment Method
                'cod_orders'              => Order::where('payment_method', Order::PAYMENT_METHOD_COD)->count(),
                'advance_orders'          => Order::where('payment_method', Order::PAYMENT_METHOD_ONLINE)->count(),

                // Financial Summary
                'total_sales'             => (float) Order::sum('amount'),
                'total_revenue'           => (float) Order::sum('payable_amount'),
                'total_paid_amount'       => (float) Order::sum('paid_amount'),
                'total_due_amount'        => (float) Order::sum('due_amount'),
                'total_shipping_charge'   => (float) Order::sum('shipping_charge'),
                'total_discount'          => (float) Order::sum('discount'),
                'total_coupon_discount'   => (float) Order::sum('coupon_discount'),
                'total_tax'               => (float) Order::sum('tax'),
                'total_points'            => (int) Order::sum('point'),
            ];

            $paymentSummary = [

                // Status
                'total_transactions' => OrderPayment::count(),
                'pending' => OrderPayment::pending()->count(),
                'processing' => OrderPayment::processing()->count(),
                'success' => OrderPayment::success()->count(),
                'failed' => OrderPayment::failed()->count(),
                'cancelled' => OrderPayment::where('status', OrderPayment::STATUS_CANCELLED)->count(),
                'refunded' => OrderPayment::where('status', OrderPayment::STATUS_REFUNDED)->count(),

                // Payment Type
                'payment' => OrderPayment::where('payment_type', OrderPayment::TYPE_PAYMENT)->count(),
                'refund' => OrderPayment::where('payment_type', OrderPayment::TYPE_REFUND)->count(),
                'adjustment' => OrderPayment::where('payment_type', OrderPayment::TYPE_ADJUSTMENT)->count(),

                // Channel
                'online' => OrderPayment::online()->count(),
                'offline' => OrderPayment::offline()->count(),

                // Payment Method
                'cod' => OrderPayment::where('payment_method', OrderPayment::METHOD_COD)->count(),
                'cash' => OrderPayment::where('payment_method', OrderPayment::METHOD_CASH)->count(),
                'bank_transfer' => OrderPayment::where('payment_method', OrderPayment::METHOD_BANK_TRANSFER)->count(),
                'mobile_banking' => OrderPayment::where('payment_method', OrderPayment::METHOD_MOBILE_BANKING)->count(),
                'card' => OrderPayment::where('payment_method', OrderPayment::METHOD_CARD)->count(),
                'paypal' => OrderPayment::where('payment_method', OrderPayment::METHOD_PAYPAL)->count(),
                'wallet' => OrderPayment::where('payment_method', OrderPayment::METHOD_WALLET)->count(),

                // Provider
                'bkash' => OrderPayment::where('provider', OrderPayment::PROVIDER_BKASH)->count(),
                'nagad' => OrderPayment::where('provider', OrderPayment::PROVIDER_NAGAD)->count(),
                'rocket' => OrderPayment::where('provider', OrderPayment::PROVIDER_ROCKET)->count(),
                'sslcommerz' => OrderPayment::where('provider', OrderPayment::PROVIDER_SSLCOMMERZ)->count(),
                'stripe' => OrderPayment::where('provider', OrderPayment::PROVIDER_STRIPE)->count(),
                'manual' => OrderPayment::where('provider', OrderPayment::PROVIDER_MANUAL)->count(),
                'bank' => OrderPayment::where('provider', OrderPayment::PROVIDER_BANK)->count(),
                'cash_provider' => OrderPayment::where('provider', OrderPayment::PROVIDER_CASH)->count(),

                // Amount
                'total_amount' => (float) OrderPayment::sum('amount'),
                'total_gateway_fee' => (float) OrderPayment::sum('gateway_fee'),
                'total_net_amount' => (float) OrderPayment::sum('net_amount'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data fetched successfully.',
                'data' => [
                    'user' => $user,
                    'summary' => $orderSummary,
                    'payment_summary' => $paymentSummary,
                ]
            ], 200);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching dashboard data.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
