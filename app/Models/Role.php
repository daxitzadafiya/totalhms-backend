<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Role extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'company_id', 'level', 'related_id', 'permission', 'added_by'
    ];

    const COMPANY_ROLE_LEVEL = 1;
    const MANAGER_ROLE_LEVEL = 2;
    const USER_ROLE_LEVEL = 3;
    const CS_ROLE_LEVEL = 4;

    public static $rules = array(
        'name'=>'required',
    );

    public static $updateRules = array(
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
    private $level;

    /**
     * @OA\Property()
     * @var int
     */
    private $related_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $permission;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissionsOfRole()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');

    }

    public function csPermissions()
    {
        return $this->hasMany(CustomerServicePermission::class);
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