<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id','company_id', 'plan_id', 'addon_id','start_date', 'billed_at', 'deactivated_at','trial_end_at','pay_by',
        'cancelled_at','next_billing_at','plan_detail','addon_detail','quantity'
    ];

    protected $casts = [
        'plan_detail' => 'array',
        'addon_detail' => 'array',
    ];

    const CARD = 1;
    const INVOICE = 2;

    public function scopeActive($query)
    {
        return $query->whereNull('deactivated_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function billing()
    {
        return $this->hasOne(Billing::class);
    }

    public function getCreatedAtAttribute($value)
    {
        $timezone = "Europe/Oslo";
        if(Auth::check()){
            $company = Company::find(Auth::user()->company_id);
            $timezone = $company->time_zone ?? 'Europe/Oslo';
        }
        return Carbon::parse($value)->timezone($timezone)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        $timezone = "Europe/Oslo";
        if(Auth::check()){
            $company = Company::find(Auth::user()->company_id);
            $timezone = $company->time_zone ?? 'Europe/Oslo';
        }
        return Carbon::parse($value)->timezone($timezone)->format('Y-m-d H:i:s');
    }
}
