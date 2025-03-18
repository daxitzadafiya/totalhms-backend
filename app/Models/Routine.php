<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Routine"))
 */
class Routine extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'routines';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'company_id', 'status' , 'category_id', 'project_id', 'department_id', 'job_title_id',
        'responsible_id', 'added_by', 'is_template',
        'industry_id', 'is_public', 'is_suggestion', 'delete_status', 'parent_id',
        'is_activated', 'start_time', 'deadline', 'recurring','is_duration',
        'is_attending_activated', 'attending_emps', 'attending_contact', 'attendings_count','recurring_type','start_date','duration','used_count'
    ];

    public static $rules = array(
        'name'=>'required',
        'status'=>'required',
        'added_by'=>'required|numeric|exists:users,id',
        'industry_id'=>'required',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'status'=>'required|sometimes',
        'added_by'=>'required|numeric|exists:users,id|sometimes',
        'industry_id'=>'required|sometimes',
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
    private $description;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $status;

    /**
     * @OA\Property()
     * @var int
     */
    private $category_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $project_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $department_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $start_time;

    /**
     * @OA\Property()
     * @var int
     */
    private $deadline;

    /**
     * @OA\Property()
     * @var string
     */
    private $recurring;

    /**
     * @OA\Property()
     * @var string
     */
    private $attending_emps;

    /**
     * @OA\Property()
     * @var string
     */
    private $attending_contact;

    /**
     * @OA\Property()
     * @var int
     */
    private $responsible_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    /**
     * @OA\Property()
     * @var int
     */
    private $attendings_count;

    /**
     * @OA\Property()
     * @var string
     */
    private $industry_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $job_title_id;

    public function schedule()
    {
        return $this->hasOne(Schedule::class, 'routine_id');
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
