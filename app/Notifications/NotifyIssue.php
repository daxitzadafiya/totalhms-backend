<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Setting;

class NotifyIssue extends Notification
{
    use Queueable;
    public $message;
    public $from_address;
    public $from_name;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($message)
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
        $this->message = $message;
        $this->from_address = @$settingSMTP['value_details']['from_address']??config('mail.from.address');
        $this->from_name = @$settingSMTP['value_details']['from_name']??config('mail.from.name');
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
        return (new MailMessage)
            ->from($this->from_address, $this->from_name)
            ->subject('Service issues message')
            ->line('Service issues message')
            ->line($this->message)
            ->line('Thanks!');
    }

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