<?php

namespace App\Http\Responses\Signup;

use Illuminate\Http\JsonResponse;

class VerifyEmailResponse
{
    /**
     * Prepare the response for a successful token generation.
     *
     * @param string $token
     * @return JsonResponse
     */
    public function success(string $email): JsonResponse
    {
        return response()->json([
            'email' => $email,
        ], 200);
    }

    /**
     * Prepare the response for an error (e.g., validation or unexpected failure).
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
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
