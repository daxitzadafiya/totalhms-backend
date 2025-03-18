<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="ConnectTo"))
 */
class ConnectTo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'connect_to';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by', 'object_id', 'document_id', 'connect_to_source', 'source_id'
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
    private $connect_to_source;

    /**
     * @OA\Property()
     * @var int
     */
    private $source_id;

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
