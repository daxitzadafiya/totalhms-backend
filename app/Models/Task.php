<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Task"))
 */
class Task extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'company_id', 'department_id', 'project_id', 'job_title_id', 'status' ,
        'completed_time', 'added_by', 'industry_id', 'type_id', 'is_public', 'is_suggestion', 'responsible_id',
        'assigned_company', 'assigned_employee', 'assigned_department', 'completed_by', 'description', 'update_history',
        'type_main_id',
        'start_time', 'deadline', 'recurring','object_id'
    ];

    public static $rules = array(
        'name'=>'required',
        'type'=>'required',
        'status'=>'required',
        'added_by'=>'required|numeric|exists:users,id',
        'industry_id'=>'required',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'type'=>'required|sometimes',
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
    private $type;

    /**
     * @OA\Property()
     * @var int
     */
    private $type_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $department_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $project_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $job_title_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

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
    private $completed_time;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    /**
     * @OA\Property()
     * @var string
     */
    private $industry_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $responsible_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $completed_by;

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

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    public function task_assignees() {
        return $this->hasMany(TaskAssignee::class, 'task_id', 'id');
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
