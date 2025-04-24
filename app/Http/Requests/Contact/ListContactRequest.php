<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ListContactRequest extends FormRequest
{

    protected $validSortKeys = [
        'first_name',
        'last_name',
        'nick_name',
        'birthday',
        'country',
        'zip_code',
        'email',
        'phone',
        'created_at',
        'updated_at',
    ];

    public function rules()
    {
        return [
            'limit' => 'integer|min:1|max:500',
            'page' => 'integer|min:1|max:400',
            'sort_key' => [
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    // Validate if sort_key exists in predefined valid keys
                    if (!in_array($value, $this->validSortKeys)) {
                        $fail("The value '{$value}' for {$attribute} is not a valid column.");
                    }
                },
            ],
            'sort_order' => 'string|in:asc,desc',
            'search' => 'string|max:1000',
        ];
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
