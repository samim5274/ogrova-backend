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

            /*
            |--------------------------------------------------------------------------
            | Advance Payment
            |--------------------------------------------------------------------------
            */

            'trans_payment_method' => [
                'bail',
                Rule::requiredIf(fn () => $this->payment_method === 'advance'),
                Rule::in(['mobile', 'bank']),
            ],

            'bank_name' => [
                'bail',
                'sometimes',
                Rule::requiredIf(fn () => $this->payment_method === 'advance'),
                'string',
                'max:255',
            ],

            'account_number' => [
                'bail',
                'sometimes',
                Rule::requiredIf(fn () => $this->payment_method === 'advance'),
                'string',
                'regex:/^[A-Za-z0-9\s\-]+$/',
                'max:100',
            ],

            'transaction_id' => [
                'bail',
                'sometimes',
                Rule::requiredIf(
                    fn () =>
                        $this->payment_method === 'advance'
                        && $this->trans_payment_method === 'mobile'
                ),
                'string',
                'max:100',
                Rule::unique('order_payments', 'transaction_id'),
            ],

            'account_holder_name' => [
                'bail',
                'sometimes',
                Rule::requiredIf(
                    fn () =>
                        $this->payment_method === 'advance'
                        && $this->trans_payment_method === 'bank'
                ),
                'string',
                'max:255',
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
            'address_id.exists'   => 'Selected shipping address is invalid.',

            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in'       => 'Invalid payment method selected.',

            'remarks.max' => 'Remarks may not be greater than 1000 characters.',

            'trans_payment_method.required' => 'Please select a payment type.',
            'trans_payment_method.in'       => 'Invalid payment type.',

            'bank_name.required' => 'Bank / Mobile Banking name is required.',
            'bank_name.in'       => 'Invalid Bank / Mobile Banking name.',

            'account_number.required' => 'Account number is required.',
            'account_number.regex'    => 'Invalid account number.',

            'transaction_id.required' => 'Transaction ID is required.',
            'transaction_id.unique'   => 'This transaction ID has already been used.',

            'account_holder_name.required' => 'Account holder name is required.',
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
