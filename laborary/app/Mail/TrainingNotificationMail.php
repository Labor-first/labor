<?php

namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $content;

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【培训通知】' . $this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.training-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
