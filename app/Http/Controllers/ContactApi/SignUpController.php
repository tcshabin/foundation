<?php

namespace App\Http\Controllers\ContactApi;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use \Tymon\JWTAuth\Exceptions\TokenExpiredException;
use \Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Http\Requests\Signup\SignUpVerificationRequest;
use App\Http\Requests\Signup\UserRegisterRequest;
use App\Http\Responses\Signup\SignUpVerificationResponse;
use App\Http\Responses\Signup\VerifyEmailResponse;
use App\Http\Responses\Signup\UserRegisterResponse;
use App\Helpers\TokenHelper;
use App\Mail\SendEmailVerificationToken;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class SignUpController extends Controller
{

    protected $now;

    const DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    public function __construct()
    {
        $this->now = Carbon::now();
    }

    /**
     * Endpoint to initiate email verification.
     *
     * @param SignUpVerificationRequest $request
     *   The request object containing the email address.
     * @param SignUpVerificationResponse $signUpResponse
     *   The response object, which will contain either a success message or
     *   an error.
     * @return \Illuminate\Http\JsonResponse
     *   The response object, which will contain either a success message or
     *   an error.
     */
    public function sendVerificationEmail(SignUpVerificationRequest $request, SignUpVerificationResponse $signUpResponse)
    {
        $email = $request->input('email');

        // Flag to determine request source (app or web)
        $isAppRequest = $request->attributes->get('is_app_request', false);

        try {

            $customClaims = [
                'email'   => $email,
                'purpose' => 'email_verification',
                'sub'     => $email,
                'exp'     => $this->now->copy()->addMinutes(Config::get('jwt.signup_ttl', 1440))->timestamp,
            ];

            $token = TokenHelper::generateToken($customClaims);

            Mail::to($email)->send(new SendEmailVerificationToken(Crypt::encryptString($token), $isAppRequest));

            return $signUpResponse->success('Verification initiated', 200);
        } catch (\Throwable $e) {
            Log::error('Email verification sent failed', [
                'email' => $email,
                'exception_message' => $e->getMessage(),
            ]);
            return $signUpResponse->generalError('Failed to send email. Please try again.', 500);
        }
    }

    /**
     * Verify an email using the provided JWT token.
     *
     * This method decodes the email from the given token and returns a successful response
     * if the token is valid. If the token is expired or invalid, it returns an appropriate
     * error response. In case of any other exceptions, it logs the error and returns
     * a general error response.
     *
     * @param string $emailToken
     *   The JWT token containing the email to be verified.
     * @param VerifyEmailResponse $verifyEmailResponse
     *   The response object, which will contain either a success message or an error.
     * @return \Illuminate\Http\JsonResponse
     *   The response object, which will contain either a success message with the email
     *   or an error message.
     */

    public function verifyEmail($emailToken, VerifyEmailResponse $verifyEmailResponse)
    {
        $verifyResponse = null;

        try {
            // Attempt to decode the JWT token and retrieve the email from its payload
            $email = TokenHelper::getPayloadData(Crypt::decryptString($emailToken));
            $verifyResponse = $verifyEmailResponse->success($email);
        } catch (TokenExpiredException $e) {
            $verifyResponse = $verifyEmailResponse->error([
                'email_token' => 'Token expired'
            ], 400);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            $verifyResponse = $verifyEmailResponse->error([
                'email_token' => 'Invalid email token'
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Verify email failed', [
                'exception_message' => $e->getMessage(),
            ]);
            $verifyResponse = $verifyEmailResponse->generalError('Verification failed. Please try again.', 500);
        }

        return $verifyResponse;
    }

    /**
     * Handles user registration.
     *
     * This method uses the provided email token to verify the user's email and
     * creates a new user account if the email is not already registered.
     * It generates a token response for the newly created user upon successful
     * registration. In case of any failure, it logs the error and returns an
     * appropriate error response.
     *
     * @param UserRegisterRequest $request
     *   The request object containing the user's registration details.
     * @param UserRegisterResponse $userRegisterResponse
     *   The response object, which will contain either a success message with
     *   the token data or an error message.
     * @return \Illuminate\Http\JsonResponse
     *   The response object, which will contain either a success message with
     *   the token data or an error message.
     */

    public function signup(UserRegisterRequest $request, UserRegisterResponse $userRegisterResponse)
    {
        $registerResponse = null;

        try {

            $email = TokenHelper::getPayloadData(Crypt::decryptString($request->email_token), 'email');

            if (User::where('email', $email)->exists()) {
                $registerResponse = $userRegisterResponse->error(['email' => 'Email address already registered'], 400);
            } else {
                DB::beginTransaction();

                $user = $this->createUser($request, $email);

                $tokenData = TokenHelper::generateTokenResponse($user, $this->now, $request->attributes->get('is_app_request', false));

                DB::commit();

                return $userRegisterResponse->success($tokenData);
            }
        } catch (TokenExpiredException $e) {
            $registerResponse = $userRegisterResponse->error(['email' => 'Token expired'], 400);
        } catch (TokenInvalidException $e) {
            $registerResponse = $userRegisterResponse->error(['email' => 'Invalid email token'], 400);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('register failed', [
                'exception_message' => $e->getMessage(),
            ]);
            $registerResponse = $userRegisterResponse->generalError('Failed. Please try again.', 500);
        }

        return $registerResponse;
    }

    /**
     * Create a new user in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $email
     * @return \App\Models\User
     */
    private function createUser($request, $email)
    {
        return User::create([
            'name'     => $request->name,
            'email'    => $email,
            'password' => Hash::make($request->password),
        ]);
    }

}
