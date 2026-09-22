<?php

namespace App\Http\Requests\Auth;

use App\Enums\ErrorCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfirmPasswordRequest extends FormRequest
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
            'phone'    => ['required', 'string', 'max:15', 'regex:/^[0-9]+$/', 'between:5,12'],
            'password'   => ['required', 'string', 'min:6'],
            'token' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'Invalid phone number.',
            'phone.between' => 'Phone must be between 5 and 12 digits.',
            'phone.regex' => 'Phone must be numbers only.',
            'password.required' => 'Invalid password.',
            'password.min' => 'Password must be at least 6 characters.',
            'token.*' => 'Invalid token',
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
