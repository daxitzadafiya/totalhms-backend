<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Deviation"))
 */
class Deviation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'deviations';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'added_by', 'place', 'consequence_for', 'subject', 'description', 'prososial_action', 'corrective_action',
        'happened_before', 'specifications', 'attachment', 'status', 'company_id', 'report_as_anonymous', 'action',
        'department_id', 'project_id', 'job_title_id', 'responsible', 'is_public', 'is_suggestion', 'category_id','place_detail','consequence_detail'
    ];

    public static $rules = array(
        'subject' => 'required',
        'status' => 'required',
        'added_by' => 'required',
        'company_id' => 'required|numeric',
    );

    public static $updateRules = array(
        'subject' => 'required|sometimes',
        'status' => 'required|numeric|sometimes',
        'added_by' => 'required|numeric|sometimes',
        'company_id' => 'required|numeric|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $place;

    /**
     * @OA\Property()
     * @var string
     */
    private $consequence_for;

    /**
     * @OA\Property()
     * @var string
     */
    private $subject;

    /**
     * @OA\Property()
     * @var int
     */
    private $category_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    /**
     * @OA\Property()
     * @var string
     */
    private $prososial_action;

    /**
     * @OA\Property()
     * @var string
     */
    private $corrective_action;

    /**
     * @OA\Property()
     * @var string
     */
    private $action;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $happened_before;

    /**
     * @OA\Property()
     * @var string
     */
    private $specifications;

    /**
     * @OA\Property()
     * @var string
     */
    private $attachment;

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
    private $responsible;

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

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'type_id', 'id')
            ->where('type', '=', 'Deviation');
    }

    public function risk_analysis()
    {
        return $this->hasMany(RiskAnalysis::class, 'deviation_id', 'id');
    }

    public function place()
    {
        return $this->hasOne(Places::class, 'id', 'place');
    }

    public function consequence_for()
    {
        return $this->hasOne(Consequences::class, 'id', 'consequence_for');
    }
}
