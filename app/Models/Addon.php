<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Addon extends Model
{
    use SoftDeletes;
    protected $table = 'addons';

    protected $fillable = ['title', 'price', 'volume','frequency','description','fiken_product_number','fiken_addon_id'];

    const MONTHLY = 1;
    const QUARTERLY = 3;
    const HALF_YEARLY = 6;
    const ANNUALLY = 12;

    public static $rules = array(
        'title'   => 'required|string',
        'price'  => 'required|numeric',
        'volume' => 'required|numeric',
        'frequency' => 'required|numeric',
    );

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function ActiveAddons()
    {
        return $this->hasMany(Subscription::class)->whereNull('deactivated_at');
    }
        
    public function ActiveAddon()
    {
        return $this->hasOne(Subscription::class, 'addon_id')->where('user_id', Auth::id())->whereNull('deactivated_at');
    }
}
