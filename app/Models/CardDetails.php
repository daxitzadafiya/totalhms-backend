<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardDetails extends Model
{
    protected $table = 'card_details';

    protected $fillable = ['user_id', 'last4', 'exp', 'brand','exp_month','exp_year','status','stipe_payment_id'];
    
    const SETACTIVE = 1;
    const ACTIVETED = 2;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}