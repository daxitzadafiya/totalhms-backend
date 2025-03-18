<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="IntervalSetting"))
 */
class IntervalSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'interval_setting';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'type', 'added_by', 'year', 'month', 'day', 'hour', 'minute', 'status'
    ];

    public static $rules = array(
        'added_by'=>'required|numeric|exists:users,id'
    );

    public static $updateRules = array(
        'added_by'=>'required|numeric|exists:users,id|sometimes'
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $type;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    /**
     * @OA\Property()
     * @var int
     */
    private $year;

    /**
     * @OA\Property()
     * @var int
     */
    private $month;

    /**
     * @OA\Property()
     * @var int
     */
    private $day;

    /**
     * @OA\Property()
     * @var int
     */
    private $hour;

    /**
     * @OA\Property()
     * @var int
     */
    private $minute;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

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
