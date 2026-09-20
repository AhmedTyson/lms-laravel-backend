<?php

namespace App\Support;

use Exception;
use Propaganistas\LaravelPhone\PhoneNumber;

// Single E.164 normalization point: request pre-validation + model mutator.
final class PhoneNormalizer
{
    public static function toE164(mixed $value, ?string $country = null): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $phone = new PhoneNumber($value, $country ?? config('lms.phone.default_country', 'EG'));

            return $phone->isValid() ? $phone->formatE164() : null;
        } catch (Exception) {
            return null;
        }
    }
}
