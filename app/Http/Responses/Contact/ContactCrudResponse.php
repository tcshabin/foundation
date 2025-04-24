<?php

namespace App\Http\Responses\Contact;

use Illuminate\Http\JsonResponse;

class ContactCrudResponse
{
    /**
     * Prepare the response for a successful user registration.
     *
     * @param  int     $status
     * @return JsonResponse
     */
    public function success($data, int $status = 200): JsonResponse
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
