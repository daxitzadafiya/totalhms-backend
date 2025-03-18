<?php
namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;
class MailResetPasswordNotification extends ResetPassword
{
    use Queueable;
    public $from_address;
    public $from_name;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $settingSMTP = Setting::where('key','smtp_system')->where('is_disabled',1)->first();
        if($settingSMTP){
            $transport = app('swift.transport');
            $smtp = $transport->driver(@$settingSMTP['value_details']['mailer']);
            $smtp->setHost(@$settingSMTP['value_details']['host']);
            $smtp->setPort(@$settingSMTP['value_details']['port']);
            $smtp->setUsername(@$settingSMTP['value_details']['user_name']);
            $smtp->setPassword(@$settingSMTP['value_details']['password']);
            $smtp->setEncryption(@$settingSMTP['value_details']['encryption']);
        }
        $this->from_address = @$settingSMTP['value_details']['from_address']??config('mail.from.address');
        $this->from_name = @$settingSMTP['value_details']['from_name']??config('mail.from.name');
        parent::__construct($token);
    }
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $reset_uri = '/reset/password/';
        $link = url( env('APP_URL').$reset_uri.$this->token );
        return ( new MailMessage )
            ->from($this->from_address, $this->from_name)
            ->subject( 'Reset Password Notification' )
            ->line( "Hello! You are receiving this email because we received a password reset request for your account." )
            ->action( 'Reset Password', $link )
            ->line( "This password reset link will expire in ".config('auth.passwords.users.expire')." minutes" )
            ->line( "If you did not request a password reset, no further action is required." );    }
    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
