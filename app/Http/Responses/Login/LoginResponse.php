<?php

namespace App\Http\Responses\Login;

use Illuminate\Http\JsonResponse;

class LoginResponse
{
    /**
     * Send a success response.
     *
     * @param  array   $data
     * @param  string  $message
     * @param  int     $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    /**
     * Send an error response.
     *
     * @param  string  $message
     * @param  int     $status
     * @return \Illuminate\Http\JsonResponse
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
