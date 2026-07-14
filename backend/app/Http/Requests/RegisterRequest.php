<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z\s]+$/'],
            'phone_num' => ['required', 'string',  'max:13','unique:customers,phone_num', 'regex:/^(09|\+959|959)[0-9]{7,9}$/'],
            'email' => ['required', 'email', 'max:100', 'unique:customers,email'],
            'password' => ['required', 'min:8', 'confirmed']
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
