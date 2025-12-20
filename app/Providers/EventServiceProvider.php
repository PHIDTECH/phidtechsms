<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Bryceandy\Selcom\Events\CheckoutWebhookReceived::class => [
            \App\Listeners\ProcessSelcomWebhook::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}

