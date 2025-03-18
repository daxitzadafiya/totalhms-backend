<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailContent extends Model
{
    protected $fillable = [
        'key',
        'title',
        'subject',
        'source_code',
        'is_sms',
        'description',
        'sms_description',
    ];

    public static $rules = array(
        'key'   => 'string',
        'title' => 'string',
        'subject' => 'string',
        'source_code'   => 'required',
        'description'   => 'required',
    );
}