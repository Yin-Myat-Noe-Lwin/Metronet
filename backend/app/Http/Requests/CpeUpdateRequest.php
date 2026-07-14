<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CpeUpdateRequest extends FormRequest
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
            'serial_number' => [
            'sometimes',
            'string',
            'max:100',
            Rule::unique('cpes', 'serial_number')->ignore($this->route('id'))
        ],
        'mac_address' => [
            'sometimes',
            'string',
            'max:100',
            Rule::unique('cpes', 'mac_address')->ignore($this->route('id')),
            'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
        ],
        ];
    }

    public function messages(): array
    {
        return [
            'mac_address.regex' => 'Invalid Mac Address'
        ];
    }
}
