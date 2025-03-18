<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @OA\Schema(@OA\Xml(name="User"))
 */
class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name','email', 'password','company_id','role_id','address','city','zip_code','phone_number','personal_number','avatar', 'added_by', 'status','customer_stripe_id','payment_method',
    ];

    public static $rules = array(
        'first_name'=>'required',
//        'last_name'=>'required',
        'email'=>'required|email',
        'phone_number'=>'required',
//        'personal_number'=>'required',
    );

    public static $updateRules = array(
        'first_name'=>'required|sometimes',
//        'last_name'=>'required|sometimes',
        'email'=>'required|email|sometimes',
        'phone_number'=>'required|sometimes',
//        'personal_number'=>'required|sometimes',
    );

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function setPasswordAttribute($password)
    {
        if (!empty($password)) {
            $this->attributes['password'] = bcrypt($password);
        }
    }
    public function setPhoneNumberAttribute($phone_number)
    {
        if (!empty($phone_number) ) {
            $phone_number = trim($phone_number);
            if (strlen($phone_number) == 8) {
                $first = substr($phone_number, 0, 3);
                $second = substr($phone_number, 3, 2);
                $third = substr($phone_number, 5, 3);

                $this->attributes['phone_number'] = $first . ' ' . $second . ' ' . $third;
            } else {
                $this->attributes['phone_number'] = $phone_number;
            }
        }
    }
    public function setPersonalNumberAttribute($personal_number)
    {
        if (!empty($personal_number) ) {
            $personal_number = trim($personal_number);
            if (strlen($personal_number) == 11) {
                $first = substr($personal_number, 0, 6);
                $second = substr($personal_number, 6, 5);

                $this->attributes['personal_number'] = $first . ' ' . $second;
            } else {
                $this->attributes['personal_number'] = $personal_number;
            }
        }
    }

    /**
     * Override the mail body for reset password notification mail.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token));
    }

    /**
     * @OA\Property()
     * @var string
     */
    private $first_name;

    /**
     * @OA\Property()
     * @var string
     */
    private $last_name;

    /**
     * @OA\Property()
     * @var string
     */
    private $email;

    /**
     * @OA\Property()
     * @var string
     */
    private $password;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $role_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $address;

    /**
     * @OA\Property()
     * @var string
     */
    private $city;

    /**
     * @OA\Property()
     * @var int
     */
    private $zip_code;

    /**
     * @OA\Property()
     * @var string
     */
    private $phone_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $personal_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $avatar;

    /**
     * @OA\Property()
     * @var string
     */
    private $status;

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoiceHistories()
    {
        return $this->hasMany(InvoiceHistory::class, 'user_id');
    }

//    public function permissions()
//    {
//        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id');
//    }

    public function hasPermission($permission)
    {
        foreach ($this->permissions as $per) {
            if ($per->key === $permission) {
                return true;
            }
        }
        return false;
    }

    public function hasAccess(...$permissions)
    {
        if ($this->role->name == 'Super admin' && !$this->role->company_id ) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function permissions()
    {
        return $this->hasOne(UserPermission::class, 'user_id', 'id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->whereNull('deactivated_at');
    }

    public function orders()
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function cardDetail()
    {
        return $this->hasMany(CardDetails::class,'user_id');
    }

    public function cardActive()
    {
        return $this->hasOne(CardDetails::class,'user_id')->where('status',2);
    }

    public function planActive()
    {
        return $this->hasOne(Subscription::class,'user_id')->whereNull('deactivated_at')->whereNull('addon_id');
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