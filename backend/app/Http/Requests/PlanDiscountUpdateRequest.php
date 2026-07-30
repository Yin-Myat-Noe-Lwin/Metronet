<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanDiscountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is admin
        return auth()->check() && auth()->user()->role === 0;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $discountId = $this->route('id') ?? $this->route('discount');

        return [
            'plan_id' => [
                'sometimes',
                'integer',
                'exists:isp_plans,id'
            ],
            'duration_months' => [
                'sometimes',
                'integer',
                'in:1,3,6,12,24',
                Rule::unique('plan_discounts')
                    ->where(function ($query) {
                        return $query->where('plan_id', $this->plan_id ?? $this->route('discount')->plan_id)
                                     ->where('duration_months', $this->duration_months);
                    })
                    ->ignore($discountId)
            ],
            'discount_percentage' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100'
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.exists' => 'The selected plan does not exist.',
            'duration_months.in' => 'Duration must be 1, 3, 6, 12, or 24 months.',
            'duration_months.unique' => 'A discount for this plan and duration already exists.',
            'discount_percentage.min' => 'Discount percentage cannot be negative.',
            'discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
            'is_active.boolean' => 'Status must be either active or inactive.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure is_active is properly cast
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}
