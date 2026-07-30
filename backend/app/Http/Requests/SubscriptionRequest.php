<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class SubscriptionRequest extends FormRequest
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
            'duration_months' => [
                'required',
                'integer',
                'min:1',
                'max:24' // Allow any month
            ],
            'billing_cycle' => [
                'sometimes',
                'integer',
                'in:1,3,6,12'// limit billing cycle
            ],
            'address_id' => [
                'sometimes',
                'exists:customer_addresses,id'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'duration_months.required' => 'Please select a subscription duration.',
            'duration_months.integer' => 'Duration must be a valid number.',
            'duration_months.min' => 'Duration must be at least 1 month.',
            'duration_months.max' => 'Duration cannot exceed 24 months.',
            'billing_cycle.in' => 'Invalid billing cycle selected. Choose 1, 3, 6, or 12 months.',
            'address_id.exists' => 'The selected address is invalid.'
        ];
    }
}
