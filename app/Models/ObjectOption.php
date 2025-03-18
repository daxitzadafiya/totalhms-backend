<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="ObjectOption"))
 */
class ObjectOption extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'objects_option';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'object_id', 'show_in_risk_analysis', 'number_used_time', 'risk_analysis_array', 'image_id'
    ];

    public static $rules = array(
        'object_id'=>'numeric|exists:objects,id',
    );

    public static $updateRules = array(
        'object_id'=>'numeric|exists:objects,id|sometimes',
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $object_id;

    /**
     * @OA\Property()
     * @var bool
     */
    private $show_in_risk_analysis;

    /**
     * @OA\Property()
     * @var int
     */
    private $number_used_time;

    /**
     * @OA\Property()
     * @var string
     */
    private $risk_analysis_array;

    /**
     * @OA\Property()
     * @var int
     */
    private $image_id;

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
