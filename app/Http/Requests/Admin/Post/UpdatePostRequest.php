<?php

namespace App\Http\Requests\Admin\Post;

use App\Enums\ErrorCode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
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
