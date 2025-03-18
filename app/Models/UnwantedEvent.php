<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="UnwantedEvent"))
 */


class UnwantedEvent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unwanted_events';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by', 'start', 'end', 'project_id', 'title', 'event_id', 'description', 'place', 'work_operation'
    ];

    public static $rules = array(
        'company_id'=>'required|numeric',
        'added_by'=>'required|numeric|exists:users,id',
        'project_id'=>'required|numeric',
        'title'=>'required'
    );

    public static $updateRules = array(
        'company_id'=>'required|numeric|sometimes',
        'added_by'=>'required|numeric|exists:users,id|sometimes',
        'project_id'=>'required|numeric|sometimes',
        'title'=>'required|sometimes'
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
     * @var string
     */
    private $start;

    /**
     * @OA\Property()
     * @var string
     */
    private $end;

    /**
     * @OA\Property()
     * @var int
     */
    private $project_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $title;

    /**
     * @OA\Property()
     * @var int
     */
    private $event_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    /**
     * @OA\Property()
     * @var string
     */
    private $place;

    /**
     * @OA\Property()
     * @var string
     */
    private $work_operation;

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
