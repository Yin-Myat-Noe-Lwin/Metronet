<?php

namespace App\Http\Requests;

use App\Models\ServiceArea;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerAddressUpdateRequest extends FormRequest
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
            'address' => ['sometimes', 'max:255'],
            'township' => [
                'sometimes',
                function (string $attribute, mixed $value, Closure $fail) {

                    $exists = ServiceArea::where('region', request('region'))
                        ->where('city', request('city'))
                        ->where($attribute, $value)
                        ->where('status', 1)
                        ->exists();

                    if (!$exists) {
                        $fail('Invalid region, city and township combination.');
                    }
                }
            ],
            'city' => [
                'sometimes',
                Rule::exists('service_areas','city')
            ],
            'region' => [
                'sometimes',
                Rule::exists('service_areas', 'region')
            ],
            'address_type' => ['sometimes', 'integer', 'in:1,2,3'],
        ];
    }
}
