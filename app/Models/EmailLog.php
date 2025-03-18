<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'status',
        'description',
        'for_admin',
        'for_company'
    ];

    const SENT = 1;
    const FAIL = 2;

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}