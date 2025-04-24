<?php

namespace App\Http\Responses\Login;

use Illuminate\Http\JsonResponse;

class ResetPasswordResponse
{
    /**
     * Prepare the response for a successful token generation.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function success(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 200);
    }

    public function error(array $messages, int $statusCode = 400): JsonResponse
    {
        // Format the error response for multiple keys
        $formattedErrors = [];

        foreach ($messages as $key => $message) {
            $formattedErrors[] = [
                $key => $message
            ];
        }

        return response()->json([
            'error' => $formattedErrors
        ], $statusCode);
    }

    public function generalError(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'error' => $message,
        ], $statusCode);
    }
}
