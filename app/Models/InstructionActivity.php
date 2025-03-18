<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="InstructionActivity"))
 */
class InstructionActivity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'instruction_activities';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'instruction_id', 'activity', 'assignee', 'assigned_employee', 'assigned_department'
    ];


    public static $rules = array(
        'instruction_id'=>'required|numeric|exists:instructions,id',
        'activity'=>'required',
    );

    public static $updateRules = array(
        'instruction_id'=>'required|numeric|exists:instructions,id|sometimes',
        'activity'=>'required|sometimes',
    );


    /**
     * @OA\Property()
     * @var int
     */
    private $instruction_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $activity;

    /**
     * @OA\Property()
     * @var int
     */
    private $assignee;

    /**
     * @OA\Property()
     * @var string
     */
    private $assigned_employee;

    /**
     * @OA\Property()
     * @var string
     */
    private $assigned_department;

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
