<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AttendeeHistory extends Model
{
    protected $table = 'attendee_history';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'object_id', 'type', 'reason', 'old_attendee_department', 'old_attendee_employee', 'new_attendee_department', 'new_attendee_employee','transfer_information','transfer_feedback','transfer_attachment','updated_by'
    ];

    public static $rules = array(
        'object_id'=>'numeric',
        'updated_by'=>'numeric',
        'type'=>'required',
    );

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
