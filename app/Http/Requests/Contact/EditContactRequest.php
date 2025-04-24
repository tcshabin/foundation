<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class EditContactRequest extends FormRequest
{
    private const STRING_100 = 'string|max:100';

    public function rules()
    {

        return [
            'first_name' =>  self::STRING_100,
            'last_name' => 'required|' . self::STRING_100,
            'nick_name' => self::STRING_100,
            'web_url' => 'string|max:1000',
            'address' => 'string|max:1000',
            'birthday' => 'date_format:Y-m-d',
            'notes' => 'string|max:3000',
            'country' => 'string|max:10',
            'zip_code' => 'string|max:15',

            'phone_numbers'             => 'array',
            'phone_numbers.*.phone' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('contact_phone', 'phone')
                    ->where(function ($query) {
                        if ($this->route('contactId')) {
                            $query->where('contact_id', '!=', $this->route('contactId'));
                        }
                    }),
            ],

            'phone_numbers.*.tag'       => 'nullable|string|max:50',
            'phone_numbers.*.is_primary' => 'boolean',

            'emails'                    => 'array',
            'emails.*.email' => [
                'nullable',
                'email',
                'string',
                'max:255',
                Rule::unique('contact_email', 'email')
                    ->where(function ($query) {
                        if ($this->route('contactId')) {
                            $query->where('contact_id', '!=', $this->route('contactId'));
                        }
                    }),
            ],
            'emails.*.tag'              => 'nullable|string|max:50',
            'emails.*.is_primary'       => 'boolean',
        ];
    }

    /**
     * Add custom validation logic after default rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check duplicate phone numbers within request
            $phones = collect($this->input('phone_numbers', []))->pluck('phone');
            if ($phones->duplicates()->isNotEmpty()) {
                $validator->errors()->add('phone_numbers', 'Duplicate phone numbers found in request.');
            }

            // Check duplicate emails within request
            $emails = collect($this->input('emails', []))->pluck('email');
            if ($emails->duplicates()->isNotEmpty()) {
                $validator->errors()->add('emails', 'Duplicate email addresses found in request.');
            }
        });
    }

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
