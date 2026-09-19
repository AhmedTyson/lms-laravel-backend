<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $defaultCountry = config('lms.phone.default_country', 'EG');
        $allowedCountries = config('lms.phone.allowed_countries', ['EG']);
        $allowInternational = config('lms.phone.allow_international', true);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => [
                'nullable',
                'string',
                'unique:users,phone_number',
                function ($attribute, $value, $fail) use ($allowInternational) {
                    // Normalize input string: strip spaces, dashes, parentheses
                    $clean = preg_replace('/[^\d+]/', '', $value);

                    // Check Egyptian Mobile National Format (11 digits: 010, 011, 012, 015)
                    $isEgyptNationalMobile = (bool) preg_match('/^01[0125]\d{8}$/', $clean);

                    // Check Egyptian Mobile E.164 International Format (+2010, +2011, +2012, +2015 + 8 digits)
                    $isEgyptInternationalMobile = (bool) preg_match('/^\+201[0125]\d{8}$/', $clean);

                    // Check General International Format if enabled (+ followed by 7 to 15 digits)
                    $isGeneralInternational = $allowInternational && preg_match('/^\+\d{7,15}$/', $clean);

                    if (! $isEgyptNationalMobile && ! $isEgyptInternationalMobile && ! $isGeneralInternational) {
                        $fail(__('The :attribute must be a valid Egyptian mobile number (11 digits starting with 010, 011, 012, 015) or valid E.164 international format.'));
                    }
                },
            ],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'role' => ['required', 'string', 'in:student,instructor'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }
}
