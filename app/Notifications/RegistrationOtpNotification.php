<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationOtpNotification extends Notification
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
            ->subject('Mã OTP xác thực tài khoản CineON')
            ->greeting('Xin chào!')
            ->line('Bạn vừa đăng ký tài khoản CineON.')
            ->line("Mã OTP xác thực của bạn là: {$this->otp}")
            ->line('Mã có hiệu lực trong 10 phút và chỉ được sử dụng một lần.')
            ->line('Nếu bạn không thực hiện đăng ký này, hãy bỏ qua email.');
    }
}
