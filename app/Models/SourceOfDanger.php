<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="SourceOfDanger"))
 */
class SourceOfDanger extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'source_of_danger';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by', 'object_id', 'name', 'probability', 'consequence', 'comment', 'need_to_process','visible_to_others'
    ];

    public static $rules = array(
        'company_id'=>'numeric|exists:companies,id',
        'added_by'=>'numeric|exists:users,id',
        'name'=>'required',
    );

    public static $updateRules = array(
        'company_id'=>'numeric|exists:companies,id|sometimes',
        'added_by'=>'numeric|exists:users,id|sometimes',
        'name'=>'required|sometimes',
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
     * @var string
     */
    private $name;

    /**
     * @OA\Property()
     * @var string
     */
    private $probability;

    /**
     * @OA\Property()
     * @var string
     */
    private $consequence;

    /**
     * @OA\Property()
     * @var string
     */
    private $comment;

    /**
     * @OA\Property()
     * @var bool
     */
    private $need_to_process;

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
