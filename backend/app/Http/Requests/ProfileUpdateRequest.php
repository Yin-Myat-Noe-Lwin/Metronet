<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
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
        $customerId = auth()->id();

        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z\s]+$/'],

            'phone_num' => [
                'sometimes',
                'string',
                'max:13',
                'regex:/^(09|\+959|959)[0-9]{7,9}$/',
                Rule::unique('customers', 'phone_num')->ignore($customerId),
            ],

            'email' => [
                'sometimes',
                'email',
                'max:100',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name may only contain letters and spaces.',
            'phone_num.regex' => 'Invalid Myanmar phone number'
        ];
    }
}
