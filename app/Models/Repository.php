<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Repository"))
 */
class Repository extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'repositories';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by', 'object_name', 'object_type', 'object_id', 'date_of_permanent_deletion',
        'restore_date', 'restore_by', 'deleted_date', 'attachment_uri', 'attachment_id', 'attachment_size'
    ];

    public static $rules = array(
        'added_by'=>'required|numeric|exists:users,id',
        'restore_by'=>'numeric|exists:users,id',
    );

    public static $updateRules = array(
        'added_by'=>'required|numeric|exists:users,id|sometimes',
        'restore_by'=>'numeric|exists:users,id|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $object_name;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $object_type;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    /**
     * @OA\Property()
     * @var string
     */
    private $date_of_permanent_deletion;

    /**
     * @OA\Property()
     * @var int
     */
    private $object_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $restore_date;

    /**
     * @OA\Property()
     * @var string
     */
    private $deleted_date;

    /**
     * @OA\Property()
     * @var int
     */
    private $restore_by;

    /**
     * @OA\Property()
     * @var int
     */
    private $attachment_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $attachment_uri;

    /**
     * @OA\Property()
     * @var float
     */
    private $attachment_size;

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
