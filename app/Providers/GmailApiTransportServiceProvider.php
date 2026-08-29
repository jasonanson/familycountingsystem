<?php

namespace App\Providers;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class GmailApiTransportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 註冊 gmail-api mailer transport
        Mail::extend('gmail-api', function () {
            return new GmailApiTransport();
        });
    }
}