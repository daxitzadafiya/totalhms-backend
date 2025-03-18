<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Company"))
 */
class Company extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'phone_number', 'vat_number', 'industry_id', 'address', 'city', 'zip_code', 'logo', 'ceo', 'hse_manager',
        'safety_manager', 'is_freeze', 'status', 'active_since', 'established_date', "email", "website", "country", 'language','subscription_deactivated_at','time_zone'
    ];

    public static $rules = array(
        'name' => 'required',
        'phone_number' => 'required',
        'vat_number' => 'required',
        'industry_id' => 'required|numeric',
        'address' => 'required',
        'city' => 'required',
        'active_since' => 'required',
        'status' => 'required',
    );

    public static $updateRules = array(
        'name' => 'required|sometimes',
        'phone_number' => 'required|sometimes',
        'vat_number' => 'required|sometimes',
        'industry_id' => 'required|numeric|sometimes',
        'address' => 'required|sometimes',
        'city' => 'required|sometimes',
        'active_since' => 'required|sometimes',
        'status' => 'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $name;

    /**
     * @OA\Property()
     * @var string
     */
    private $phone_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $vat_number;

    /**
     * @OA\Property()
     * @var int
     */
    private $industry_id;

    /**
     * @var string
     * @OA\Property()
     */
    private $address;

    /**
     * @var string
     * @OA\Property()
     */
    private $city;

    /**
     * @var int
     * @OA\Property()
     */
    private $zip_code;

    /**
     * @var string
     * @OA\Property()
     */
    private $logo;

    /**
     * @var int
     * @OA\Property()
     */
    private $ceo;


    /**
     * @var int
     * @OA\Property()
     */
    private $hse_manager;

    /**
     * @var int
     * @OA\Property()
     */
    private $safety_manager;

    /**
     * @var string
     * @OA\Property()
     */
    private $status;

    /**
     * @OA\Property()
     * @var string
     */
    private $active_since;

    /**
     * @OA\Property()
     * @var string
     */
    private $established_date;

    /**
     * @OA\Property()
     * @var string
     */
    private $website;

    /**
     * @OA\Property()
     * @var string
     */
    private $email;

    /**
     * @OA\Property()
     * @var string
     */
    private $country;

    /**
     * @OA\Property()
     * @var string
     */
    private $language;

    public function billing()
    {
        return $this->hasOne(Billing::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'company_id')->whereNull('deactivated_at');
    }

    public function planActive()
    {
        return $this->hasOne(Subscription::class,'company_id')->whereNull('deactivated_at')->whereNull('addon_id');
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'company_id');
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
