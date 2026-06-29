<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceAreaRequest extends FormRequest
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
            'region' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('service_areas')
                    ->where(fn ($q) =>
                        $q->where('city', $this->city)
                        ->where('township', $this->township)
                    )
                    ->ignore($this->route('id'))
            ],
            'city' => 'required|string|max:30',
            'township' => 'required|string|max:30',
        ];
    }
}
