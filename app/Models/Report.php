<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Report"))
 */
class Report extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reports';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'answer', 'description', 'company_id' , 'added_by', 'category_id', 'project_id', 'department_id',
        'job_title_id', 'checklist_info', 'status', 'is_public', 'is_suggestion', 'responsible', 'action_done','checklist_id'
    ];

    public static $rules = array(
        'answer'=>'required',
        'company_id'=>'required|numeric|exists:companies,id',
        'added_by'=>'required|numeric|exists:users,id',
        'checklist_info'=>'required',
    );

    public static $updateRules = array(
        'answer'=>'required|sometimes',
        'company_id'=>'required|numeric|exists:companies,id||sometimes',
        'added_by'=>'required|numeric|exists:users,id|sometimes',
        'checklist_info'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $answer;

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
     * @var int
     */
    private $added_by;

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
     * @var string
     */
    private $checklist_info;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

    /**
     * @OA\Property()
     * @var string
     */
    private $action_done;

    /**
     * @OA\Property()
     * @var int
     */
    private $job_title_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $responsible;

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'type_id', 'id')
            ->where('type', '=', 'Report');
    }

    public function risk_analysis()
    {
        return $this->hasMany(RiskAnalysis::class, 'report_id', 'id');
    }

    public function reportSecurity()
    {
        return $this->hasOne(Security::class, 'object_id')->where('object_type', 'report checklist')->latest();
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
