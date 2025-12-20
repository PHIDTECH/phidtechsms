<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'webhooks/*',
        'api/*',
        // Allow Selcom payment gateway to POST webhook callbacks
        'payments/webhook',
        // Allow Selcom package routes (prefix may vary via SELCOM_PREFIX)
        'selcom/*',
        'phidtechsms/*',
    ];
}
