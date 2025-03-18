<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Invite extends Model
{
    protected $table = 'invites';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name','email', 'password','company_id','role_id','department_id','job_title_id','address',
        'city','phone_number','personal_number','avatar','zip_code','added_by','status','token','expired_time'
    ];
    protected $hidden = [
        'password'
    ];

    const PENDING = 1;
    const ACCEPTED = 2;
    const REJECTED = 3;

    public static $rules = array(
        'first_name'=>'required',
        'email'=>'required|email',
        'department_id'=>'nullable|numeric|exists:departments,id',
    );

    public static $updateRules = array(
        'first_name'=>'required|sometimes',
        'email'=>'required|email|sometimes',
        'department_id'=>'nullable|numeric|exists:departments,id|sometimes',

    );

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
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
