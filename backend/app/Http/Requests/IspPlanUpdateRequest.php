<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IspPlanUpdateRequest extends FormRequest
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
            'name' => [
                        'sometimes',
                        'string',
                        'max:255',
                        Rule::unique('isp_plans', 'name')->ignore($this->route('id'))
                    ],
            'description' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0|max:999999',
            'validity_months' => 'sometimes|integer|min:1|max:12',
            'upload_speed' => 'sometimes|integer|min:1|max:200',
            'download_speed' => 'sometimes|integer|min:1|max:200'
        ];
    }

    public function messages(): array
    {
        return[
            'validity_months.min' => 'Validity must be at least 1 month',
            'upload_speed.min' => 'Upload speed must be at least 1 Mbps',
            'download_speed.min' => 'Download speed must be at least 1 Mbps'
        ];
    }
}
