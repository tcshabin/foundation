<?php

namespace App\Http\Requests\Login;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email_token' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
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
            'password.regex' => 'Invalid password',
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
