<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="DocumentNew"))
 */
class DocumentNew extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents_new';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'industry_id', 'name', 'description', 'status', 'added_by',
        'delete_status', 'is_template', 'parent_id', 'type_of_attachment', 'type',
        'object_type', 'object_id', 'category_id', 'is_suggestion','is_reminder','task_id','routine_id'
    ];
    // ,'is_reminder','task_id','is_shared'

    public static $rules = array(
        'name'=>'required',
//        'industry_id'=>'required',
        'added_by'=>'required|numeric',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
//        'industry_id'=>'required|sometimes',
        'added_by'=>'required|numeric|sometimes',
    );

    public static $fileRules = array(
        'file'=>'required|mimes:jpeg,png,jpg,gif,svg,pdf,csv,xlsx,doc,docx,ppt,pptx,ods,odt,odp,application/csv,application/excel,application/vnd.ms-excel, application/vnd.msexcel,text/csv,text/anytext,text/plain,text/x-c,text/comma-separated-values,inode/x-empty,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;



    /**
     * @OA\Property()
     * @var string
     */
    private $industry_id;

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
    private $delete_status;

    /**
     * @OA\Property()
     * @var int
     */
    private $type_of_attachment;

    /**
     * @OA\Property()
     * @var string
     */
    private $type;

    /**
     * @OA\Property()
     * @var string
     */
    private $object_type;

    /**
     * @OA\Property()
     * @var int
     */
    private $object_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $category_id;

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

    public function tasks() {
        return $this->hasMany(Task::class, 'type_id', 'id')
            ->where('type', '=', 'document');
    }

    public function security() {
        return $this->hasOne(Security::class, 'object_id', 'id')
            ->where('object_type', '=', 'document');
    }

    // public function routines() {
    //     return $this->hasOne(Routine::class, 'id', 'routine_id');
    // }
}
