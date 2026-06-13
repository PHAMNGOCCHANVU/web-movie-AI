<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $otp
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mã OTP đặt lại mật khẩu CineON')
            ->greeting('Xin chào!')
            ->line('Bạn vừa yêu cầu đặt lại mật khẩu tài khoản CineON.')
            ->line("Mã OTP của bạn là: {$this->otp}")
            ->line('Mã có hiệu lực trong 10 phút và chỉ được sử dụng một lần.')
            ->line('Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email.');
    }
}
