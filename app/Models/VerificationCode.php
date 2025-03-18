<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="VerificationCode"))
 */
class VerificationCode extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'verification_codes';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'user_id', 'action', 'code', 'expired_time', 'email'
    ];

    public static $rules = array(
        'code'=>'required',
        'user_id'=>'required|numeric|exists:users,id',
        'company_id'=>'required|numeric|exists:companies,id',
    );

    public static $updateRules = array(
        'code'=>'required|sometimes',
        'user_id'=>'required|numeric|exists:users,id|sometimes',
        'company_id'=>'required|numeric|exists:companies,id|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $email;

    /**
     * @OA\Property()
     * @var string
     */
    private $action;

    /**
     * @OA\Property()
     * @var string
     */
    private $code;

    /**
     * @OA\Property()
     * @var int
     */
    private $user_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
