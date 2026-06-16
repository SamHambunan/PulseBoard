<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * validation rules for tenant registration.
 *
 * this request validates the data required to create:
 * - a new tenant/company
 * - the initial owner account for that tenant
 *
 * validation requirements:
 * - company and user names are required
 * - email must be unique across all users
 * - password must be at least 8 characters and match
 *   the password_confirmation field
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
