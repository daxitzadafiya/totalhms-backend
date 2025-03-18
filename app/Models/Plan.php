<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Plan extends Model
{
    use SoftDeletes;
    protected $table = 'plans';

    protected $fillable = [
        'title', 'description', 'total_users', 'user_per_storage', 'price','plan_type', 'additional_price', 'free_trial_months','plan_detail','fiken_product_number','fiken_additional_id','fiken_plan_id',
    ];

    const MONTHLY = 1;
    const QUARTERLY = 3;
    const HALF_YEARLY = 6;
    const ANNUALLY = 12;


    protected $casts = [
        'plan_detail' => 'array',
    ];

    public static $rules = array(
        'title'            => 'required|string',
        'description'      => 'required|string',
        'total_users'      => 'required|numeric',
        'user_per_storage' => 'required|numeric',
        'price'            => 'required|numeric',
        'plan_type'        => 'required|numeric',
        'free_trial_months' => 'required|numeric',
    );

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function ActiveSubscriptions()
    {
        return $this->hasMany(Subscription::class)->whereNull('deactivated_at');
    }

    public function ActiveSubscription()
    {
        return $this->hasOne(Subscription::class, 'plan_id')->where('user_id', Auth::id())->whereNull('deactivated_at');
    }
}
