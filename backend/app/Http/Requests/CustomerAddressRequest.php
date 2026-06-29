<?php

namespace App\Http\Requests;

use App\Models\ServiceArea;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerAddressRequest extends FormRequest
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
            'address' => ['required', 'max:255'],
            'township' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) {

                    $exists = ServiceArea::where('region', request('region'))
                        ->where('city', request('city'))
                        ->where($attribute, $value)
                        ->where('status', 1)
                        ->exists();

                    if (!$exists) {
                        $fail('Invalid service area combination.');
                    }
                }
            ],
            'city' => [
                'required',
                Rule::exists('service_areas','city')
            ],
            'region' => [
                'required',
                Rule::exists('service_areas', 'region')
            ],
            'address_type' => ['required', 'integer', 'in:1,2,3'],
        ];
    }
}
