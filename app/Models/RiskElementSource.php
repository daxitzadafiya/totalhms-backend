<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="RiskElementSource"))
 */
class RiskElementSource extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'risk_element_sources';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'description', 'type', 'type_id', 'added_by', 'name', 'department_id', 'job_title_id', 'project_id'
        , 'is_public', 'is_suggestion', 'delete_status', 'industry_id', 'is_template', 'update_history', 'category_id'
    ];

    public static $rules = array(
        'added_by'=>'required|numeric|exists:users,id',
        'name'=>'required',
        'industry_id'=>'required',
    );

    public static $updateRules = array(
        'added_by'=>'required|numeric|exists:users,id|sometimes',
        'name'=>'required|sometimes',
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
    private $industry_id;

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
    private $job_title_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $project_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $delete_status;

    public function user() {
        return $this->belongsTo(User::class, 'added_by', 'id');
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
