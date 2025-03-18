<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="RiskAnalysis"))
 */
class RiskAnalysis extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'risk_analysis';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'name', 'status', 'added_by', 'department_id', 'project_id', 'job_title_id', 'need_to_process',
        'deviation_id', 'report_id', 'is_public', 'is_suggestion', 'responsible','object_id'
    ];

    public static $rules = array(
        'company_id'=>'required|numeric|exists:companies,id',
        'name'=>'required',
        'added_by'=>'required|numeric|exists:users,id',
    );

    public static $updateRules = array(
        'company_id'=>'required|numeric|exists:companies,id||sometimes',
        'name'=>'required|sometimes',
        'added_by'=>'required|numeric|exists:users,id|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $name;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

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
    private $need_to_process;

    /**
     * @OA\Property()
     * @var int
     */
    private $deviation_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $report_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $responsible;


    public function user() {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function elements() {
        return $this->hasMany(RiskElement::class, 'risk_analysis_id', 'id');
    }

    public function tasks() {
        return $this->hasMany(Task::class, 'type_id', 'id')
            ->where('type', '=', 'Risk analysis');
    }

    public function deviation()
    {
        return $this->belongsTo(Deviation::class, 'deviation_id', 'id');
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
