<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CouponMail extends Mailable
{
    use Queueable, SerializesModels;
    public $emailContent;
    public $subject;
    public $from_address;
    public $from_name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject,$emailContent)
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
        $this->emailContent = $emailContent;
        $this->subject = $subject;
        $this->from_address = @$settingSMTP['value_details']['from_address']??config('mail.from.address');
        $this->from_name = @$settingSMTP['value_details']['from_name']??config('mail.from.name');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($this->from_address, $this->from_name)->view('emails.coupon')->subject($this->subject);
    }
}