<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Document"))
 */
class Document extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'original_file_name', 'category_id', 'company_id', 'type', 'added_by',
        'description', 'uri', 'contact_id', 'employee_id', 'deviation_id', 'added_from',
        'industry_id', 'is_template', 'report_id', 'report_question_id', 'type_of_attachment',
        'is_renewed', 'renewed_option', 'renewed_department_array', 'renewed_job_title_array',
        'renewed_project_array', 'renewed_employee_array', 'deadline',
        'is_public', 'show_manager', 'added_by_department_id', 'security_department_array',
        'security_project_array', 'security_employee_array', 'risk_element_source_id',
        'absence_id', 'project_id', 'delete_status', 'parent_id', 'help_center_id', 'status',
        'file_size'
    ];

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
     * @var string
     */
    private $name;

    /**
     * @OA\Property()
     * @var string
     */
    private $original_file_name;

    /**
     * @OA\Property()
     * @var int
     */
    private $category_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $type;

    /**
     * @OA\Property()
     * @var integer
     */
    private $added_from;

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    /**
     * @OA\Property()
     * @var string
     */
    private $uri;

    /**
     * @OA\Property()
     * @var int
     */
    private $contact_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $industry_id;

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
    private $employee_id;

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
    private $report_question_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $type_of_attachment;

    /**
     * @OA\Property()
     * @var int
     */
    private $renewed_option;

    /**
     * @OA\Property()
     * @var string
     */
    private $renewed_department_array;

    /**
     * @OA\Property()
     * @var string
     */
    private $renewed_job_title_array;

    /**
     * @OA\Property()
     * @var string
     */
    private $renewed_project_array;

    /**
     * @OA\Property()
     * @var string
     */
    private $renewed_employee_array;

    /**
     * @OA\Property()
     * @var string
     */
    private $deadline;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by_department_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $security_employee_array;

    /**
     * @OA\Property()
     * @var string
     */
    private $security_department_array;

    /**
     * @OA\Property()
     * @var string
     */
    private $security_project_array;

    /**
     * @OA\Property()
     * @var int
     */
    private $risk_element_source_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $absence_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $delete_status;

    /**
     * @OA\Property()
     * @var string
     */
    private $help_center_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

    /**
     * @OA\Property()
     * @var int
     */
    private $file_size;

    public function tasks() {
        return $this->hasMany(Task::class, 'type_id', 'id')
            ->where('type', '=', 'Document');
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
