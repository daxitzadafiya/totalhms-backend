<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerServicePermission extends Model
{
    protected $fillable = [
        'module',
        'is_enabled',
        'role_id'
    ];
}