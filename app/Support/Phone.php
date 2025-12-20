<?php

namespace App\Support;

class Phone
{
    public static function normalizeTZ(string $raw): string
    {
        $value = preg_replace('/\s+/', '', $raw);

        if (preg_match('/^0(7\d{8})$/', $value, $matches)) {
            return '+255' . $matches[1];
        }

        if (!str_starts_with($value, '+')) {
            $value = '+' . $value;
        }

        return $value;
    }
}
