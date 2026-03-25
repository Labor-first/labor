<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordCode extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $username;

    public function __construct(string $code, string $username)
    {
        $this->code = $code;
        $this->username = $username;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '重置密码验证码',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
