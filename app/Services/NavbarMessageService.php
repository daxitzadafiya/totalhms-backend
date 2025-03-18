<?php

namespace App\Services;


use App\Message;

class NavbarMessageService
{
    protected $messages;

    public function __construct()
    {
        $this->messages = Message::with('author')
            ->where('recipient_id', \Auth::id())
            ->get();
    }

    public function get()
    {
        return $this->messages;
    }
}