<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Employee"))
 */
class Employee extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employees';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'department_id', 'nearest_manager', 'hourly_salary', 'overtime_pay', 'night_allowance', 'holidays',
        'tax', 'weekend_addition', 'evening_allowance', 'account_number', 'absence_info', 'job_title_id', 'disable_status'
    ];

    public static $rules = array(
        'user_id'=>'required|numeric|exists:users,id',
        'department_id'=>'numeric|exists:departments,id',
    );

    public static $updateRules = array(
        'user_id'=>'required|numeric|exists:users,id|sometimes',
        'department_id'=>'numeric|exists:departments,id|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $absence_info;

    /**
     * @OA\Property()
     * @var int
     */
    private $user_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $department_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $nearest_manager;

    /**
     * @OA\Property()
     * @var float
     */
    private $hourly_salary;

    /**
     * @OA\Property()
     * @var float
     */
    private $overtime_pay;

    /**
     * @OA\Property()
     * @var int
     */
    private $night_allowance;

    /**
     * @OA\Property()
     * @var int
     */
    private $holidays;

    /**
     * @OA\Property()
     * @var float
     */
    private $tax;

    /**
     * @OA\Property()
     * @var int
     */
    private $weekend_addition;

    /**
     * @OA\Property()
     * @var int
     */
    private $evening_allowance;

    /**
     * @OA\Property()
     * @var string
     */
    private $account_number;

    /**
     * @OA\Property()
     * @var int
     */
    private $job_title_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $disable_status;

    public function userPermission()
    {
        return $this->hasOne(UserPermission::class, 'user_id', 'user_id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
