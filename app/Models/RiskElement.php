<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="RiskAnalysis"))
 */
class RiskElement extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'risk_elements';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'risk_analysis_id', 'name', 'type', 'type_id', 'probability', 'consequence', 'description_resolve',
    ];

    public static $rules = array(
        'risk_analysis_id'=>'required|numeric|exists:risk_analysis,id',
        'name'=>'required',
    );

    public static $updateRules = array(
        'risk_analysis_id'=>'required|numeric|exists:risk_analysis,id||sometimes',
        'name'=>'required|sometimes',
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
    private $type;

    /**
     * @OA\Property()
     * @var int
     */
    private $risk_analysis_id;

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
    private $description_resolve;

    /**
     * @OA\Property()
     * @var int
     */
    private $type_id;

    public function risk_analysis() {
        return $this->belongsTo(RiskAnalysis::class, 'risk_analysis_id', 'id');
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
