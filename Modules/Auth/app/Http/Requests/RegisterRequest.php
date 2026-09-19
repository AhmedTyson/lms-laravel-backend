<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
        $allowedCountries = config('lms.phone.allowed_countries', ['EG']);
        $allowInternational = config('lms.phone.allow_international', true);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone_number'),
                function ($attribute, $value, $fail) use ($allowInternational) {
                    $clean = preg_replace('/[^\d+]/', '', $value);
                    $isEgyptNationalMobile = (bool) preg_match('/^01[0125]\d{8}$/', $clean);
                    $isEgyptInternationalMobile = (bool) preg_match('/^\+201[0125]\d{8}$/', $clean);
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
