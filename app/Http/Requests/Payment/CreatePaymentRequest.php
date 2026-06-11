<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    $this->filled(['plan_code', 'billing_cycle'])
                    && ! str_ends_with(
                        $this->string('plan_code')->toString(),
                        $this->string('billing_cycle')->toString()
                    )
                ) {
                    $validator->errors()->add(
                        'billing_cycle',
                        'Chu kỳ thanh toán không khớp với gói cước đã chọn.'
                    );
                }
            },
        ];
    }
}
