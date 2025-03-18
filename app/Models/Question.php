<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Topic"))
 */
class Question extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'questions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'company_id', 'status' , 'added_by', 'topic_id', 'checklist_id', 'default_option_id','required_comment','required_attachment'
    ];

    public static $rules = array(
        'name'=>'required',
        'status'=>'required',
        'topic_id'=>'required|numeric|exists:topics,id',
        'checklist_id'=>'required|numeric|exists:checklists,id',
        'added_by'=>'required|numeric|exists:users,id'
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'status'=>'required|sometimes',
        'topic_id'=>'required|numeric|exists:topics,id|sometimes',
        'checklist_id'=>'required|numeric|exists:checklists,id|sometimes',
        'added_by'=>'required|numeric|exists:users,id|sometimes'
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
    private $topic_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $default_option_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    /**
     * @OA\Property()
     * @var int
     */
    private $checklist_id;

    public function reports()
    {
        return $this->hasMany(Report::class);
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
