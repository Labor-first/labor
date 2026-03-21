<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendActivationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    // 定义要传递给邮件模板的变量（激活码、用户名）
    public $activationCode; // 激活码
    public $username;       // 用户名
    public $expireMinutes;  // 有效期（分钟）

    /**
     * 构造函数：接收外部传入的参数
     * @param string $username 用户名
     * @param string $activationCode 6位激活码
     * @param int $expireMinutes 有效期（默认30分钟）
     */
    public function __construct(string $username, string $activationCode, int $expireMinutes = 30)
    {
        $this->username = $username;
        $this->activationCode = $activationCode;
        $this->expireMinutes = $expireMinutes;
    }

    /**
     * 构建邮件内容（核心）
     */
    public function build()
    {
        return $this
            ->subject("【实验室管理系统】账号激活码") // 邮件标题
            ->view('emails.activation_code'); // 邮件模板路径
    }
}