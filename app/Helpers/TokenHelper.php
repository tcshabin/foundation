<?php

namespace App\Helpers;

use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;

class TokenHelper
{

    const DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    /**
     * Generates a JWT token containing the given custom claims.
     *
     * @param array $customClaims
     *   The custom claims to include in the JWT payload.
     * @return string
     *   The generated JWT token.
     */
    public static function generateToken(array $customClaims): string
    {
        $payload = JWTFactory::customClaims($customClaims)->make();
        return JWTAuth::encode($payload)->get(); // Returns the JWT string
    }

    /**
     * Retrieve specific data from the JWT payload.
     *
     * @param string $token
     *   The JWT token from which the payload data is to be retrieved.
     * @param mixed $payloadType
     *   The specific key or type of data to retrieve from the payload.
     *   If null, the entire payload is returned.
     * @return mixed
     *   The value associated with the specified payload type, or the entire
     *   payload if no specific type is provided.
     */

    public static function getPayloadData(string $token, $payloadType = null)
    {
        $payload = JWTAuth::setToken($token)->getPayload();

        return $payloadType ? $payload->get($payloadType) : $payload;
    }


    /**
     * Generate a JWT token response containing both an access token and a refresh token.
     *
     * @param \App\Models\User $user
     *   The user for which the token response is to be generated.
     * @param \Carbon\Carbon $now
     *   The current time, used to calculate the expiration times of the generated tokens.
     * @return array
     *   An associative array containing the access token and refresh token, each with their
     *   respective expiration times.
     */
    public static function generateTokenResponse(User $user, Carbon $now, $isAppRequest): array
    {
        $ttl = Config::get('jwt.' . ($isAppRequest ? 'app_ttl' : 'ttl'), 60);
        $refreshTokenTtl = Config::get('jwt.' . ($isAppRequest ? 'app_refresh_ttl' : 'refresh_ttl'), 1440);

        $customClaims = [
            'purpose' => 'access',
            'user_id' => $user->id,
            'exp' => $now->copy()->addMinutes($ttl)->timestamp,
        ];

        // Access Token
        $token = JWTAuth::claims($customClaims)->fromUser($user);
        
        $accessPayload = self::getPayloadData($token);
        $accessExpiry = $now->createFromTimestamp($accessPayload['exp'])->format(self::DATE_FORMAT);

        // Refresh Token
        $refreshClaims = [
            'purpose' => 'refresh',
            'user_id' => $user->id,
            'password' => $user->password,
            'exp' => $now->copy()->addMinutes($refreshTokenTtl)->timestamp,
        ];
        $refreshToken = JWTAuth::claims($refreshClaims)->fromUser($user);
        $refreshPayload = self::getPayloadData($refreshToken);
        $refreshExpiry = $now->createFromTimestamp($refreshPayload['exp'])->format(self::DATE_FORMAT);

        return [
            'access_token' => [
                'token' => Crypt::encryptString($token),
                'expiry' => $accessExpiry,
            ],
            'refresh_token' => [
                'token' => Crypt::encryptString($refreshToken),
                'expiry' => $refreshExpiry,
            ]
        ];
    }
}
