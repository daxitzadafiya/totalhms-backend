<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
