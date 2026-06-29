<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('isp_plans', 'name')->ignore($this->route('id'))
                    ],
            'description' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'upload_speed' => 'sometimes|integer|min:1',
            'download_speed' => 'sometimes|integer|min:1',
        ];
    }
}
