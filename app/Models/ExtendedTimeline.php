<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="EmployeeRelation"))
 */
class ExtendedTimeline extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'extended_timeline';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'object_id', 'process_id', 'old_deadline', 'deadline_date', 'deadline_time', 'reason', 'requested_by', 'requested_by_name','extended_by','extended_by_name','extended_by_reason','status','type'
    ];


    public static $rules = array(
        'object_id'=>'required',
        'process_id'=>'required', 
        'requested_by'=>'required', 
    );

    public static $updateRules = array(
        'object_id'=>'required|sometimes',
        'process_id'=>'required|sometimes', 
        'requested_by'=>'required|sometimes', 
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
