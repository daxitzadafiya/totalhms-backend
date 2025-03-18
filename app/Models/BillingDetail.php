<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingDetail extends Model
{
    protected $table = 'billing_details';

    protected $fillable = [
        'billing_id', 'plan_id', 'addon_id','additional_user','additional_user_amount','discount','vat', 'amount','status','fiken_invoice_id'
    ];

    const PENDING = 0;
    const PAID = 1;

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }

    public function billing()
    {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

}
