<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ActivationCodeNotification extends Notification
{
    use Queueable;

    protected $activationCode;

    // 接收激活码
    public function __construct($activationCode)
    {
        $this->activationCode = $activationCode;
    }

    // 发送渠道（邮件）
    public function via($notifiable)
    {
        return ['mail'];
    }

    // 邮件内容
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('实验室账号激活码')
            ->greeting('您好！')
            ->line('您的实验室账号激活码为：')
            ->line($this->activationCode)
            ->line('激活码有效期为 30 分钟，请尽快完成激活。')
            ->action('前往激活', url('/api/user/verify-activation-code'))
            ->line('如果您未进行此操作，请忽略此邮件。');
    }
}