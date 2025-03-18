<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Contact"))
 */
class Contact extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contacts';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'company_id', 'phone_number', 'email', 'category_id', 'address', 'city', 'zip_code',  'added_by',
        'organization_number', 'project_id', 'is_template', 'is_suggestion'
    ];

    public static $rules = array(
        'name'=>'required',
//        'company_id'=>'required|numeric',
        'phone_number'=>'required',
        'email'=>'required|email',
        'address'=>'required',
        'city'=>'required',
        'added_by'=>'required|numeric',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
//        'company_id'=>'required|numeric|sometimes',
        'phone_number'=>'required|sometimes',
        'email'=>'required|email|sometimes',
        'address'=>'required|sometimes',
        'city'=>'required|sometimes',
        'added_by'=>'required|numeric|sometimes',
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
    private $phone_number;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $email;

    /**
     * @OA\Property()
     * @var int
     */
    private $category_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $address;

    /**
     * @OA\Property()
     * @var string
     */
    private $city;

    /**
     * @OA\Property()
     * @var string
     */
    private $zip_code;

    /**
     * @OA\Property()
     * @var string
     */
    private $organization_number;

    /**
     * @OA\Property()
     * @var int
     */
    private $project_id;

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
