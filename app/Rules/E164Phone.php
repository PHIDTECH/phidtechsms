<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class E164Phone implements Rule
{
    public function passes($attribute, $value): bool
    {
        return is_string($value) && preg_match('/^\+?[1-9]\d{6,14}$/', $value);
    }

    public function message(): string
    {
        return 'Phone must be in E.164 format (e.g., +255712345678).';
    }
}
