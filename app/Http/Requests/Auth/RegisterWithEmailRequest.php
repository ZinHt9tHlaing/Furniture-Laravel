<?php

namespace App\Http\Requests\Auth;

use App\Enums\ErrorCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegisterWithEmailRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstName' => ['required', 'string', 'max:52'],
            'lastName'  => ['required', 'string', 'max:52'],
            'email'     => ['required', 'string', 'email', 'max:52', Rule::unique('users', 'email')],
            'phone'    => ['required', 'string', 'max:15', 'regex:/^[0-9]+$/', 'between:5,12', 'unique:users'],
            'password'  => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'firstName.required' => 'Please enter your first name.',
            'firstName.max'      => 'First name must not exceed 52 characters.',
            'lastName.required'  => 'Please enter your last name.',
            'lastName.max'       => 'Last name must not exceed 52 characters.',
            'email.required'     => 'Please enter your email.',
            'email.email'        => 'Please enter a valid email address.',
            'email.max'          => 'Email must not exceed 52 characters.',
            'email.unique'       => 'Email already exists.',
            'password.required'  => 'Please enter your password.',
            'password.min'       => 'Password must be at least 6 characters.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            "message" => $validator->errors()->first() ?: "Validation failed",
            // "errors"  => $validator->errors(),
            "error" => ErrorCode::Invalid->value
        ], 422));
    }
}
