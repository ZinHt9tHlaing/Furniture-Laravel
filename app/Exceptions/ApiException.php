<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiException extends Exception
{

    public function __construct(
        string $message = '',
        protected int $statusCode = 400,
        protected ErrorCode $errorCode = ErrorCode::Invalid
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode->value;
    }

    /**
     * Render the exception into an HTTP response automatically.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message'    => $this->getMessage(),
            'error_code' => $this->errorCode->value,
        ], $this->statusCode);
    }
}
