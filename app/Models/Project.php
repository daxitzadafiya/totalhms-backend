<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Project"))
 */
class Project extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'company_id', 'project_number', 'thumbnail', 'reference', 'start', 'deadline', 'status',
        'responsible', 'project_number_custom', 'added_by'
    ];

    public static $rules = array(
        'name'=>'required',
        'company_id'=>'required|numeric'
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'company_id'=>'required|numeric|sometimes'
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
     * @var string
     */
    private $project_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $thumbnail;

    /**
     * @OA\Property()
     * @var string
     */
    private $reference;

    /**
     * @OA\Property()
     * @var string
     */
    private $start;

    /**
     * @OA\Property()
     * @var string
     */
    private $deadline;

    /**
     * @OA\Property()
     * @var integer
     */
    private $status;

    /**
     * @OA\Property()
     * @var string
     */
    private $responsible;

    /**
     * @OA\Property()
     * @var string
     */
    private $project_number_custom;

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
