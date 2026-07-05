<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ConfirmOrderRequest extends FormRequest
{
    /**
     * Authorize Request
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
        return [

            /*
            |--------------------------------------------------------------------------
            | General
            |--------------------------------------------------------------------------
            */

            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'same_address' => [
                'nullable',
                'boolean',
            ],

            'save_info' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */

            'address_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('customer_addresses', 'id')
                    ->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_method' => [
                'bail',
                'required',
                Rule::in(['cod', 'advance']),
            ],

            'd_payment_method' => [
                'bail',
                Rule::requiredIf(
                    fn () => $this->payment_method === 'advance'
                ),
                Rule::in(['bank', 'mobile_banking']),
            ],

            /*
            |--------------------------------------------------------------------------
            | Bank Payment
            |--------------------------------------------------------------------------
            */

            'bank_name' => [
                'bail',
                'sometimes',
                Rule::requiredIf(
                    fn () =>
                        $this->payment_method === 'advance'
                        && $this->d_payment_method === 'bank'
                ),
                'string',
                'max:255',
            ],

            'account_number' => [
                'bail',
                'sometimes',
                Rule::requiredIf(
                    fn () =>
                        $this->payment_method === 'advance'
                        && $this->d_payment_method === 'bank'
                ),
                'string',
                'max:100',
            ],

            'account_holder_name' => [
                'bail',
                'sometimes',
                Rule::requiredIf(
                    fn () =>
                        $this->payment_method === 'advance'
                        && $this->d_payment_method === 'bank'
                ),
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Mobile Banking
            |--------------------------------------------------------------------------
            */

            'mobile_number' => [
                'bail',
                'sometimes',
                Rule::requiredIf(
                    fn () =>
                        $this->payment_method === 'advance'
                        && $this->d_payment_method === 'mobile_banking'
                ),
                'regex:/^(?:\+8801|8801|01)[3-9]\d{8}$/',
            ],

            'transaction_id' => [
                'bail',
                'sometimes',
                Rule::requiredIf(
                    fn () =>
                        $this->payment_method === 'advance'
                        && $this->d_payment_method === 'mobile_banking'
                ),
                'string',
                'max:100',

                'transaction_id' => [
                    'bail',
                    'sometimes',
                    Rule::requiredIf(
                        fn () =>
                            $this->payment_method === 'advance'
                            && $this->d_payment_method === 'mobile_banking'
                    ),
                    'string',
                    'max:100',
                    Rule::unique('transactions', 'transaction_id'),
                ],
            ],

        ];
    }

    /**
     * Custom Error Messages
     */
    public function messages(): array
    {
        return [

            'address_id.required' => 'Please select a shipping address.',
            'address_id.exists'   => 'Selected address is invalid.',

            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in'       => 'Invalid payment method.',

            'd_payment_method.required' => 'Please select an advance payment method.',
            'd_payment_method.in'       => 'Invalid advance payment method.',

            'bank_name.required'           => 'Bank name is required.',
            'account_number.required'      => 'Account number is required.',
            'account_holder_name.required' => 'Account holder name is required.',

            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.regex'    => 'Please enter a valid mobile number.',

            'transaction_id.required' => 'Transaction ID is required.',
            'transaction_id.unique'   => 'This transaction ID has already been used.',
        ];
    }

    /**
     * JSON Validation Response
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422)
        );
    }

    /**
     * JSON Unauthorized Response
     */
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
