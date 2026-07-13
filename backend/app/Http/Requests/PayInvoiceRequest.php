<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PaymentMethod;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'payment_method' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->where('is_active', 1),
            ],
        ];

        // Get payment method and add dynamic rules for required fields
        $methodId = $this->input('payment_method');
        if ($methodId) {
            $method = PaymentMethod::find($methodId);
            if ($method && $method->requires_details) {
                $fields = json_decode($method->fields, true) ?? [];
                foreach ($fields as $field => $rule) {
                    if ($rule === 'required') {
                        $rules["payment_details.$field"] = 'required|string|max:255';
                    }
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.exists' => 'Invalid payment method selected.',
        ];

        // Dynamic messages for payment details
        $methodId = $this->input('payment_method');
        if ($methodId) {
            $method = PaymentMethod::find($methodId);
            if ($method && $method->requires_details) {
                $fields = json_decode($method->fields, true) ?? [];
                foreach ($fields as $field => $rule) {
                    if ($rule === 'required') {
                        $label = str_replace('_', ' ', $field);
                        $messages["payment_details.$field.required"] = "The $label is required.";
                    }
                }
            }
        }

        return $messages;
    }
}
