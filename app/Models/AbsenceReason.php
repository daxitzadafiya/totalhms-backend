<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="AbsenceReason"))
 */
class AbsenceReason extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'absence_reasons';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'description', 'is_paid', 'company_id', 'interval_absence', 'reset_time_number',
        'reset_time_unit', 'apply_time_number', 'apply_time_unit', 'days_off', 'days_off_exception',
        'extra_alone_custody', 'sick_child_max_age', 'sick_child_max_age_handicapped', 'deadline_registration_number',
        'deadline_registration_unit', 'related_id', 'illegal', 'added_by', 'sick_child', 'class_of_absence', 'processor'
    ];

    public static $rules = array(
        'type'=>'required',
//        'reset_time_number'=>'integer',
//        'apply_time_number'=>'integer',
    );

    public static $updateRules = array(
        'type'=>'required|sometimes',
//        'reset_time_number'=>'integer|sometimes',
//        'apply_time_number'=>'integer|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $type;

    /**
     * @OA\Property()
     * @var string
     */
    private $class_of_absence;

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $is_paid;

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
    private $interval_absence;

    /**
     * @OA\Property()
     * @var int
     */
    private $reset_time_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $reset_time_unit;

    /**
     * @OA\Property()
     * @var int
     */
    private $apply_time_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $apply_time_unit;

    /**
     * @OA\Property()
     * @var int
     */
    private $days_off;

    /**
     * @OA\Property()
     * @var int
     */
    private $days_off_exception;

    /**
     * @OA\Property()
     * @var int
     */
    private $extra_alone_custody;

    /**
     * @OA\Property()
     * @var int
     */
    private $sick_child_max_age;

    /**
     * @OA\Property()
     * @var int
     */
    private $sick_child_max_age_handicapped;

    /**
     * @OA\Property()
     * @var int
     */
    private $deadline_registration_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $deadline_registration_unit;

    /**
     * @OA\Property()
     * @var int
     */
    private $related_id;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $illegal;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $sick_child;

    /**
     * @OA\Property()
     * @var string
     */
    private $processor;
}
