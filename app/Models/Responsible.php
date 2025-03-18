<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Responsible"))
 */
class Responsible extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'responsible';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by', 'object_id', 'document_id', 'employee_array', 'required_comment', 'required_attachment','department_array','required_comments_array','required_attachments_array'
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
    private $object_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $document_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $employee_array;

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

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function processing() {
        return $this->hasMany(ResponsibleProcessing::class,'attendee_id');
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
