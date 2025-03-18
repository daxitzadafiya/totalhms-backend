<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="Department"))
 */
class Department extends Model
{
    /**
     * The table associated with the model.
     *Company Info

     * @var string
     */
    protected $table = 'departments';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'company_id', 'parent_id', 'manager_job_title', 'disable_status'
    ];

    public static $rules = array(
        'name'=>'required',
        'company_id'=>'required|numeric',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'company_id'=>'required|numeric|sometimes',
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
    private $description;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $disable_status;

    /**
     * @OA\Property()
     * @var int
     */
    private $parent_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $manager_job_title;

    public function employees()
    {
        return $this->hasMany(Employee::class);
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
