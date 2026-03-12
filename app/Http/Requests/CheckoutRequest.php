<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => 'required|string|in:cod,vnpay,momo',
            'shipping_full_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address_details' => 'required|string|max:255',
            'shipping_ward' => 'required|string|max:100',
            'shipping_province' => 'required|string|max:100',
            'customer_notes' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'use_points' => 'nullable|boolean',
        ];
    }
}
