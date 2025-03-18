<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Object"))
 */
class ObjectItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'objects';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'industry', 'company_id', 'added_by', 'name', 'description', 'type', 'is_template', 'category_id', 'is_suggestion',
        'is_valid', 'status', 'source', 'source_id', 'url', 'update_history', 'required_comment', 'required_attachment','question','topics','updated_by','used_count','report_as_anonymous'
    ];

    public static $rules = array(
        'company_id'=>'numeric|exists:companies,id',
        'added_by'=>'numeric|exists:users,id',
    );

    public static $updateRules = array(
        'company_id'=>'numeric|exists:companies,id|sometimes',
        'added_by'=>'numeric|exists:users,id|sometimes',
    );
    /**
     * @OA\Property()
     * @var string
     */
    private $industry;

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
    private $name;

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
     * @var bool
     */
    private $is_template;

    /**
     * @OA\Property()
     * @var int
     */
    private $category_id;

    /**
     * @OA\Property()
     * @var bool
     */
    private $is_suggestion;


    /**
     * @OA\Property()
     * @var bool
     */
    private $is_valid;

    /**
     * @OA\Property()
     * @var string
     */
    private $status;

    /**
     * @OA\Property()
     * @var string
     */
    private $source;

    /**
     * @OA\Property()
     * @var int
     */
    private $source_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $url;

    /**
     * @OA\Property()
     * @var string
     */
    private $update_history;

    /**
     * @OA\Property()
     * @var bool
     */
    private $required_comment;

    /**
     * @OA\Property()
     * @var bool
     */
    private $required_attachment;

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

    public function attendee()
    {
        return $this->hasOne(Attendee::class, 'object_id');
    }

    public function responsible()
    {
        return $this->hasOne(Responsible::class, 'object_id');
    }

    public function time()
    {
        return $this->hasOne(TimeManagement::class, 'object_id');
    }

    public function deviationSecurity()
    {
        return $this->hasOne(Security::class, 'object_id')->where('object_type', 'deviation')->latest();
    }

    public function riskAnalysisSecurity()
    {
        return $this->hasOne(Security::class, 'object_id')->where('object_type', 'risk-analysis')->latest();
    } 
    
    public function routineSecurity()
    {
        return $this->hasOne(Security::class, 'object_id')->where('object_type', 'routine')->latest();
    }

    public function checklistSecurity() 
    {
        return $this->hasOne(Security::class, 'object_id')->where('object_type', 'checklist')->latest();
    }

    public function routine()
    {
        return $this->hasOne(Routine::class, 'id', 'source_id');
    }
}
