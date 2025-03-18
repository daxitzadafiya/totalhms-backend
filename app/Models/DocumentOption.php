<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="DocumentOption"))
 */
class DocumentOption extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents_options';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'document_id', 'is_renewed', 'renewed_employee_array', 'deadline',
        'show_manager', 'is_public', 'security_department_array',
        'security_project_array', 'security_employee_array', 'report_question_id'
    ];

    public static $rules = array(
        'document_id'=>'required|numeric',
    );

    public static $updateRules = array(
        'document_id'=>'required|numeric|sometimes',
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $document_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $renewed_employee_array;

    /**
     * @OA\Property()
     * @var int
     */
    private $deadline;

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
     * @var string
     */
    private $security_employee_array;

    /**
     * @OA\Property()
     * @var int
     */
    private $report_question_id;

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
