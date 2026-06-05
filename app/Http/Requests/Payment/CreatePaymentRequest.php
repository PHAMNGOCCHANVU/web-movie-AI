<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_code' => 'required|string|in:standard_monthly,standard_yearly,vip_monthly,vip_yearly',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'transaction_type' => 'required|string|in:purchase,renewal,upgrade',
        ];
    }
}