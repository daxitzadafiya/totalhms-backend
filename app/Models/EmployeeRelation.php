<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="EmployeeRelation"))
 */
class EmployeeRelation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employee_relations';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'user_id', 'relation', 'email', 'mobile_phone', 'work_phone', 'is_primary', 'dob', 'handicapped',
        'alone_custody'
    ];


    public static $rules = array(
        'name'=>'required',
        'user_id'=>'required',
        'relation' =>'required',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'user_id'=>'required|sometimes',
        'relation' =>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $handicapped;

    /**
     * @OA\Property()
     * @var int
     */
    private $alone_custody;

    /**
     * @OA\Property()
     * @var string
     */
    private $dob;

    /**
     * @OA\Property()
     * @var string
     */
    private $name;

    /**
     * @OA\Property()
     * @var int
     */
    private $user_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $relation;

    /**
     * @OA\Property()
     * @var string
     */
    private $email;

    /**
     * @OA\Property()
     * @var string
     */
    private $mobile_phone;

    /**
     * @OA\Property()
     * @var string
     */
    private $work_phone;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $is_primary;
}
