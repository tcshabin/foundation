<?php

namespace App\Http\Requests\Signup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class SignUpVerificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email|unique:user,email|max:255',
        ];
    }

    /**
     * Customize the error messages for validation failures.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.email' => 'invalid email address',
            'email.unique' => 'Email address already registered',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $formattedErrors = [];

        foreach ($validator->errors()->toArray() as $field => $messages) {
            foreach ($messages as $message) {
                $formattedErrors[] = [
                    $field => $message
                ];
            }
        }

        throw new ValidationException($validator, response()->json([
            'error' => $formattedErrors
        ], 400));
    }
}
