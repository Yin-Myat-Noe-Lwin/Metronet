<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CpeRequest extends FormRequest
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
            'serial_number' => 'required|string|max:100|unique:cpes,serial_number',
            'mac_address'   => [
                'required',
                'string',
                'max:100',
                'uniques:cpes,mac_addresses',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            ]

        ];
    }

    public function messages(): array
    {
        return [
            'mac_address.regex' => 'Invalid Mac Address'
        ];
    }
}
