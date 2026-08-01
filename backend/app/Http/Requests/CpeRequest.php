<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CpeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial_number' => 'required|string|max:100|unique:cpes,serial_number',

            'mac_address' => [
                'required',
                'string',
                'max:100',
                'unique:cpes,mac_address',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mac_address.regex' => 'Invalid Mac Address',
        ];
    }
}
