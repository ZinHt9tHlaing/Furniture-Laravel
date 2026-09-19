<?php

namespace App\Http\Requests\Auth;

use App\Enums\ErrorCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone'    => ['required', 'string', 'max:15', 'regex:/^[0-9]+$/', 'between:5,12', 'unique:users'],
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'Phone number is required.',
            'phone.between' => 'Phone must be between 5 and 12 digits.',
            'phone.regex' => 'Phone must be numbers only.',
            'phone.unique' => 'Phone number already exists.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            // if empty string or falsy
            "message" => $validator->errors()->first() ?: "Validation failed",
            "errors"  => $validator->errors(),
            "error_code" => ErrorCode::Invalid->value
        ], 422));
    }
}
