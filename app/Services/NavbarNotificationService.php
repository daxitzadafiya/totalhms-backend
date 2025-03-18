<?php

namespace App\Services;


use App\Notification;

class NavbarNotificationService
{
    protected $notifications;

    public function __construct()
    {
        $this->notifications = Notification::where('recipient_id', \Auth::id())->get();
    }

    public function get()
    {
        return $this->notifications;
    }
}