<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Attachment"))
 */
class Attachment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attachments';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'processing_id', 'object_id', 'added_by', 'added_by_role', 'url'
    ];

    public static $rules = array(
        'company_id'=>'numeric|exists:companies,id',
        'added_by'=>'numeric|exists:users,id',
//        'processing_id'=>'numeric|exists:attendee_processing,id',
    );

    public static $updateRules = array(
        'company_id'=>'numeric|exists:companies,id|sometimes',
        'added_by'=>'numeric|exists:users,id|sometimes',
//        'processing_id'=>'numeric|exists:attendee_processing,id|sometimes',
    );

    public static $fileRules = array(
        'file'=>'required|mimes:jpeg,png,jpg,gif,svg'
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
    private $processing_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $object_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    /**
     * @OA\Property()
     * @var string
     */
    private $added_by_role;

    /**
     * @OA\Property()
     * @var string
     */
    private $url;

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
