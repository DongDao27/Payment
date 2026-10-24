<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerResetPassword extends ResetPassword implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(string $token)
    {
        parent::__construct($token);
        $this->afterCommit();
    }

    public function toMail($notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu — Nhà sách Thu Thanh')
            ->greeting('Chào bạn,')
            ->line('Chúng tôi nhận được yêu cầu đặt lại mật khẩu tài khoản của bạn.')
            ->action('Đặt mật khẩu mới', $url)
            ->line('Liên kết có hiệu lực trong '.config('auth.passwords.users.expire').' phút và chỉ dùng một lần.')
            ->line('Nếu bạn không yêu cầu, hãy bỏ qua email này. Mật khẩu hiện tại chưa thay đổi.')
            ->salutation('Nhà sách Thu Thanh');
    }
}
