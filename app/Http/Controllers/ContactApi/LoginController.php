<?php

namespace App\Http\Controllers\ContactApi;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use \Tymon\JWTAuth\Exceptions\TokenExpiredException;
use \Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Http\Requests\Login\LoginRequest;
use App\Http\Requests\Login\RefreshTokenRequest;
use App\Http\Requests\Login\ResetPasswordRequest;
use App\Http\Requests\Login\ForgotPasswordRequest;
use App\Http\Responses\Login\ForgotPasswordResponse;
use App\Http\Responses\Login\LoginResponse;
use App\Http\Responses\Login\ResetPasswordResponse;
use App\Helpers\TokenHelper;
use App\Mail\SendEmailForgotPassword;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class LoginController extends Controller
{

    protected $now;

    const DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    public function __construct()
    {
        $this->now = Carbon::now();
    }

    /**
     * Attempt to login a user and return a JWT token.
     *
     * This endpoint takes an email and password as input and verifies them.
     * If the credentials are valid, it generates a JWT token and returns it
     * in the response. If the credentials are invalid, it returns a 401 error
     * with an error message. If any exception occurs during the process, it
     * logs the error and returns a 500 error with an error message.
     *
     * @param  LoginRequest  $request
     *   The request object containing the user's email and password.
     * @param  LoginResponse  $loginResponse
     *   The response object, which will contain either a success message with
     *   the JWT token or an error message.
     * @return \Illuminate\Http\JsonResponse
     *   The response object, which will contain either a success message with
     *   the JWT token or an error message.
     */
    public function login(LoginRequest $request, LoginResponse $loginResponse)
    {
        $credentials = $request->only('email', 'password');

        try {
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                $response = $loginResponse->generalError('Invalid credentials.', 401);
            } else {

                if (!$user || !Hash::check($credentials['password'], $user->password)) {
                    $response = $loginResponse->generalError('Invalid credentials.', 401);
                } else {

                    $tokenData = TokenHelper::generateTokenResponse($user, $this->now, $request->attributes->get('is_app_request', false));

                    return $loginResponse->success($tokenData);
                }
            }
        } catch (\Throwable $e) {
            Log::error('login failed', [
                'exception_message' => $e->getMessage(),
            ]);
            $response = $loginResponse->generalError('Login failed. Please try again.', 500);
        }

        return $response;
    }
    /**
     * Refresh an access token.
     *
     * This endpoint takes a refresh token as input and verifies it.
     * If the token is valid, it generates a new JWT token and returns it
     * in the response. If the token is invalid or has expired, it returns a 401 error
     * with an error message. If any exception occurs during the process, it
     * logs the error and returns a 500 error with an error message.
     *
     * @param  RefreshTokenRequest  $request
     *   The request object containing the refresh token.
     * @param  LoginResponse  $loginResponse
     *   The response object, which will contain either a success message with
     *   the JWT token or an error message.
     * @return \Illuminate\Http\JsonResponse
     *   The response object, which will contain either a success message with
     *   the JWT token or an error message.
     */
    public function refreshAccessToken(RefreshTokenRequest $request, LoginResponse $loginResponse)
    {
        $refreshToken = $request->input('refresh_token');
        $response = null; // Initialize the response variable

        try {
            $payload = TokenHelper::getPayloadData(Crypt::decryptString($refreshToken));

            // Check for valid refresh token purpose
            if ($payload->get('purpose') !== 'refresh') {
                $response = $loginResponse->generalError('Invalid refresh token.', 401);
            } else {
                $user = User::where('id', $payload->get('user_id'))
                    ->where('password', $payload->get('password'))
                    ->first();

                if (!$user) {
                    $response = $loginResponse->generalError('Invalid refresh token.', 401);
                } else {
                    $tokenData = TokenHelper::generateTokenResponse($user, $this->now, $request->attributes->get('is_app_request', false));
                    $response = $loginResponse->success($tokenData);
                }
            }
        } catch (\Throwable $e) {
            Log::error('refresh token failed', [
                'exception_message' => $e->getMessage(),
            ]);
            $response = $loginResponse->generalError('Refresh token is invalid or expired.', 401);
        }

        return $response;
    }


    /**
     * Forgot password endpoint.
     *
     * This endpoint takes an email address as input and looks up the associated
     * user. If the user is found, it generates a JWT token with a limited
     * lifetime and sends an email containing the token to the user. If the
     * user is not found or any exception occurs during the process, it
     * logs the error and returns a 404 error with an error message.
     *
     * @param  \App\Http\Requests\Auth\ForgotPasswordRequest  $request
     *   The request object containing the email address.
     * @param  \App\Http\Responses\Auth\ForgotPasswordResponse  $sendForgotPasswordResponse
     *   The response object, which will contain either a success message or
     *   an error.
     * @return \Illuminate\Http\JsonResponse
     *   The response object, which will contain either a success message or
     *   an error.
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordResponse $sendForgotPasswordResponse)
    {
        try {
            $email = $request->email;

            // Flag to determine request source (app or web)
            $isAppRequest =  $request->attributes->get('is_app_request', false);

            $user = User::whereEmail($request->email)->first();
            if (!$user) {
                return $sendForgotPasswordResponse->generalError('User not found', 404);
            }

            $customClaims = [
                'user_id'   => $user->id,
                'purpose' => 'password',
                'sub'     => $user->id,
                'password' => $user->password,
                'exp'     => $this->now->copy()->addMinutes(Config::get('jwt.password_ttl', 60))->timestamp,
            ];

            $token = TokenHelper::generateToken($customClaims);

            Mail::to($email)->send(new SendEmailForgotPassword(Crypt::encryptString($token), $isAppRequest));

            return $sendForgotPasswordResponse->success('Password reset email sent', 200);
        } catch (\Throwable $e) {
            Log::error('forgot password failed', [
                'exception_message' => $e->getMessage(),
            ]);
            return $sendForgotPasswordResponse->generalError('Failed. Please try again.', 404);
        }
    }

    /**
     * Resets the password for the user associated with the given JWT token.
     *
     * This endpoint takes a JWT token as input and verifies it. If the token
     * is valid, it updates the user's password and returns a success message.
     * If the token is invalid or expired, it returns an error message. If any
     * exception occurs during the process, it logs the error and returns a
     * 500 error with an error message.
     *
     * @param  \App\Http\Requests\Auth\ResetPasswordRequest  $request
     *   The request object containing the JWT token and the new password.
     * @param  \App\Http\Responses\Auth\ResetPasswordResponse  $sendForgotPasswordResponse
     *   The response object, which will contain either a success message or
     *   an error.
     * @return \Illuminate\Http\JsonResponse
     *   The response object, which will contain either a success message or
     *   an error.
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordResponse $sendForgotPasswordResponse)
    {
        $resetResponse = null;

        try {
            $payload = TokenHelper::getPayloadData(Crypt::decryptString($request->email_token));

            $user = User::where('id', $payload->get('user_id'))
                ->where('password', $payload->get('password'))
                ->first();

            if ($user) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);

                $resetResponse = $sendForgotPasswordResponse->success('Password updated', 200);
            } else {
                $resetResponse = $sendForgotPasswordResponse->generalError('User not found or token expired', 404);
            }
        } catch (TokenExpiredException $e) {
            $resetResponse = $sendForgotPasswordResponse->generalError('reset token expired', 401);
        } catch (TokenInvalidException $e) {
            $resetResponse = $sendForgotPasswordResponse->generalError('Invalid reset token', 401);
        } catch (\Throwable $e) {
            Log::error('reset password failed', [
                'exception_message' => $e->getMessage(),
            ]);
            $resetResponse = $sendForgotPasswordResponse->generalError('Reset password failed', 500);
        }

        return $resetResponse;
    }
}
