<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="ReportTask"))
 */

class ReportTask extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'report_tasks';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'company_id', 'status' , 'project_id', 'department_id', 'added_by', 'industry_id', 'job_title_id'
    ];

    public static $rules = array(
        'name'=>'required',
        'company_id'=>'required|numeric',
        'status'=>'required',
        'added_by'=>'required|numeric|exists:users,id',
        'industry_id'=>'required',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'company_id'=>'required|numeric|sometimes',
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
    private $job_title_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $industry_id;

    public function tasks() {
        return $this->hasMany(Task::class, 'type_id', 'id')
            ->where('type', '=', 'Report');
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
