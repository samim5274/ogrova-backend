<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CustomerOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        $isAdvance = $this->payment_method === 'advance';
        $isBank    = $isAdvance && $this->trans_payment_method === 'bank';
        $isMobile  = $isAdvance && $this->trans_payment_method === 'mobile';

        return [

            /*
            |--------------------------------------------------------------------------
            | Customer Information
            |--------------------------------------------------------------------------
            */

            'recipient_name' => [
                'bail',
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'bail',
                'required',
                'regex:/^(?:\+8801|01)[3-9]\d{8}$/',
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping Address
            |--------------------------------------------------------------------------
            */

            'division_id' => [
                'bail',
                'required',
                Rule::exists('divisions', 'id'),
            ],

            'district_id' => [
                'bail',
                'required',
                Rule::exists('districts', 'id'),
            ],

            'upazila_id' => [
                'bail',
                'required',
                Rule::exists('upazilas', 'id'),
            ],

            'police_station_id' => [
                'nullable',
                Rule::exists('police_stations', 'id'),
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'label' => [
                'nullable',
                Rule::in([
                    'Home',
                    'Office',
                    'Other',
                ]),
            ],

            'address' => [
                'bail',
                'required',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_method' => [
                'bail',
                'required',
                Rule::in([
                    'cod',
                    'advance',
                ]),
            ],

            'trans_payment_method' => [
                'nullable',
                Rule::requiredIf($isAdvance),
                Rule::in([
                    'mobile',
                    'bank',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Advance Payment
            |--------------------------------------------------------------------------
            */

            'bank_name' => [
                'nullable',
                Rule::requiredIf($isMobile || $isBank),
                'string',
                'max:255',
            ],

            'account_number' => [
                'nullable',
                Rule::requiredIf($isMobile || $isBank),
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\s\-]+$/',
            ],

            'transaction_id' => [
                'nullable',
                Rule::requiredIf($isMobile),
                'string',
                'max:100',
                Rule::unique('order_payments', 'transaction_id'),
            ],

            'account_holder_name' => [
                'nullable',
                Rule::requiredIf($isBank),
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */

            'coupon' => [
                'nullable',
                'string',
                'max:100',
                Rule::exists('coupons', 'code')->where(function ($query) {

                    $query->where('is_active', true);

                    $query->where(function ($q) {
                        $q->whereNull('start_date')
                          ->orWhere('start_date', '<=', now());
                    });

                    $query->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'recipient_name.required' => 'Recipient name is required.',

            'phone.required' => 'Phone number is required.',
            'phone.regex'    => 'Please enter a valid Bangladeshi phone number.',

            'division_id.required' => 'Please select a division.',
            'division_id.exists'   => 'Invalid division selected.',

            'district_id.required' => 'Please select a district.',
            'district_id.exists'   => 'Invalid district selected.',

            'upazila_id.required' => 'Please select an upazila.',
            'upazila_id.exists'   => 'Invalid upazila selected.',

            'police_station_id.exists' => 'Invalid police station selected.',

            'address.required' => 'Street address is required.',
            'address.max'      => 'Address may not exceed 1000 characters.',

            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in'       => 'Invalid payment method.',

            'trans_payment_method.required' => 'Please select an advance payment method.',
            'trans_payment_method.in'       => 'Invalid advance payment method.',

            'bank_name.required' => 'Bank / Mobile Banking name is required.',

            'account_number.required' => 'Account number is required.',
            'account_number.regex'    => 'Invalid account number format.',

            'transaction_id.required' => 'Transaction ID is required.',
            'transaction_id.unique'   => 'Transaction ID already exists.',

            'account_holder_name.required' => 'Account holder name is required.',

            'coupon.exists' => 'Invalid or expired coupon code.',

            'remarks.max' => 'Remarks may not exceed 1000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [

            'recipient_name' => 'recipient name',
            'phone' => 'phone number',

            'division_id' => 'division',
            'district_id' => 'district',
            'upazila_id' => 'upazila',
            'police_station_id' => 'police station',

            'address' => 'street address',

            'payment_method' => 'payment method',
            'trans_payment_method' => 'advance payment method',

            'bank_name' => 'bank name',
            'account_number' => 'account number',
            'transaction_id' => 'transaction ID',
            'account_holder_name' => 'account holder name',

            'coupon' => 'coupon code',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403)
        );
    }
}
