<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\TokenHelper;
use Illuminate\Support\Facades\Crypt;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        $errorResponse = null;

        try {
            $encryptedToken = $request->bearerToken();

            if (!$encryptedToken) {
                $errorResponse = response()->json(['error' => 'Token not provided'], 401);
            } else {
                $decryptedToken = Crypt::decryptString($encryptedToken);

                $payload = TokenHelper::getPayloadData($decryptedToken);

                if ($payload->get('purpose') !== 'access') {
                    $errorResponse = response()->json(['error' => 'Invalid token'], 401);
                } else {

                    $authenticate = JWTAuth::setToken($decryptedToken)->authenticate();

                    if (!$authenticate) {
                        $errorResponse = response()->json(['error' => 'Token not valid'], 401);
                    }
                }
            }
        } catch (\Throwable $e) {
            $errorResponse = response()->json(['error' => 'Token not valid'], 401);
        }

        if ($errorResponse) {
            return $errorResponse;
        }

        return $next($request);
    }
}
