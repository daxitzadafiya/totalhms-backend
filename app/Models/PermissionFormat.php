<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PermissionFormat extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions_format';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'function', 'filter_by', 'permission_name', 'permission_type',
    ];

    public static $rules = array(
        'function'=>'required',
    );

    public static $updateRules = array(
        'function'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $function;

    /**
     * @OA\Property()
     * @var string
     */
    private $filter_by;

    /**
     * @OA\Property()
     * @var string
     */
    private $permission_name;

    /**
     * @OA\Property()
     * @var string
     */
    private $permission_type;

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
