<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept `login` (email or phone), or the explicit `email` / `phone` fields.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => $this->input('login') ?? $this->input('email') ?? $this->input('phone'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }
}
