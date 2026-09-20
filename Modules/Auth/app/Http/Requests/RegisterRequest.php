<?php

namespace Modules\Auth\Http\Requests;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Propaganistas\LaravelPhone\Rules\Phone;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Canonicalise before `unique` runs so 010… and +2010… collide.
        if (($normalized = PhoneNormalizer::toE164($this->input('phone_number'))) !== null) {
            $this->merge(['phone_number' => $normalized]);
        }
    }

    public function rules(): array
    {
        $allowedCountries = config('lms.phone.allowed_countries', ['EG']);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => [
                'nullable',
                'string',
                (new Phone)->country($allowedCountries)->mobile()->international(),
                Rule::unique('users', 'phone_number'),
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
