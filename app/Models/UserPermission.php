<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserPermission extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_permissions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'job_title_id', 'permission', 'is_super', 'assign_group'
    ];

    public static $rules = array(
        'user_id'=>'required',
    );

    public static $updateRules = array(
        'user_id'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $user_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $job_title_id;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $is_super;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $assign_group;

    /**
     * @OA\Property()
     * @var string
     */
    private $permission;

    public function job_title()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
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
