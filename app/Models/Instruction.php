<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Instruction"))
 */
class Instruction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'instructions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'company_id', 'category_id', 'added_by', 'is_template', 'department_id', 'project_id',
        'job_title_id', 'industry_id', 'is_public', 'is_suggestion', 'delete_status', 'parent_id'
    ];

    public static $rules = array(
        'name'=>'required',
        'added_by'=>'required|numeric|exists:users,id',
        'industry_id'=>'required',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
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
    private $industry_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $job_title_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $delete_status;

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

    public function instructionActivities()
    {
        return $this->hasMany(InstructionActivity::class);
    }
}
