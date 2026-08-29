<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $content;
    public $details;

    public function __construct(string $title, string $content, array $details = [])
    {
        $this->title = $title;
        $this->content = $content;
        $this->details = $details;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【家庭記帳通知】{$this->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction_alert',
        );
    }
}
