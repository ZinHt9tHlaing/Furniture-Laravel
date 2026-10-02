<?php

namespace App\Http\Requests\Profile;

use App\Enums\ErrorCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadProfileRequest extends FormRequest
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
            "avatar" => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // Max 5MB
        ];
    }

    /**
     * Customize the messages
     */
    public function messages(): array
    {
        return [
            "avatar.required" => "Profile picture is required",
            "avatar.image" => "Profile picture must be an image",
            "avatar.mimes" => "Profile picture must be a jpeg, png, jpg, or webp file",
            "avatar.max" => "Profile picture must be under 5mb",
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
