<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;
    public $subject;
    public $emailContent;
    public $remainingDays;
    public $subscriptionType;
    public $from_address;
    public $from_name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject,$emailContent,$remainingDays,$subscriptionType)
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
        $this->subject = $subject;
        $this->emailContent = $emailContent;
        $this->remainingDays  = $remainingDays;
        $this->subscriptionType  = $subscriptionType;
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
        return $this->from($this->from_address, $this->from_name)->view('emails.invoice')->subject($this->subject);
    }
}