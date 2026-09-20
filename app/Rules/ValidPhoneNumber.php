<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $allowInternational = (bool) config('lms.phone.allow_international', true);
        $clean = preg_replace('/[^\d+]/', '', (string) $value);

        $isEgyptNationalMobile = (bool) preg_match('/^01[0125]\d{8}$/', $clean);
        $isEgyptInternationalMobile = (bool) preg_match('/^\+201[0125]\d{8}$/', $clean);
        $isGeneralInternational = $allowInternational && preg_match('/^\+\d{7,15}$/', $clean);

        if (! $isEgyptNationalMobile && ! $isEgyptInternationalMobile && ! $isGeneralInternational) {
            $fail('The :attribute must be a valid Egyptian mobile number or E.164 international format.');
        }
    }
}
