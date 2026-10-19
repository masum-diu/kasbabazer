<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SellerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shop_name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ];
    }
}
