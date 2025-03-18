<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="TaskAssignee"))
 */
class TaskAssignee extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_assignees';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'task_id', 'user_id', 'status',
    ];

    public static $rules = array(
        'company_id'=>'required|numeric|exists:companies,id',
        'task_id'=>'required|numeric|exists:tasks,id',
        'user_id'=>'required|numeric|exists:users,id',
    );

    public static $updateRules = array(
        'company_id'=>'required|numeric|exists:companies,id|sometimes',
        'task_id'=>'required|numeric|exists:tasks,id|sometimes',
        'user_id'=>'required|numeric|exists:users,id|sometimes',
    );
    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $task_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $user_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

    public function task() {
        return $this->belongsTo(Task::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
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
