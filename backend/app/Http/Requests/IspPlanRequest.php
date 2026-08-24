<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IspPlanRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:isp_plans,name',
            'description' => 'required|string|max:100',
            'price' => 'required|numeric|min:0|max:999999',
            'validity_months' => 'required|integer|min:1|max:12',
            'upload_speed' => 'required|integer|min:1|max:200',
            'download_speed' => 'required|integer|min:1|max:200',
        ];
    }

    public function messages(): array
    {
        return[
            'name.required' => 'Plan name is required',
            'name.unique' => 'Plan name already exists',
            'price.required' => 'Price is required',
            'validity_months.min' => 'Validity must be at least 1 month',
            'upload_speed.required' => 'Upload speed is required',
            'upload_speed.min' => 'Upload speed must be at least 1 Mbps',
            'download_speed.required' => 'Download speed is required',
            'download_speed.min' => 'Download speed must be at least 1 Mbps'
        ];
    }
}
