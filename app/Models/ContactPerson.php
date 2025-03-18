<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="ContactPerson"))
 */
class ContactPerson extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_people';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'contact_id', 'phone_number', 'email', 'is_primary', 'job_title'
    ];


    public static $rules = array(
        'name'=>'required',
        'contact_id'=>'required',
        'job_title'=>'required',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'contact_id'=>'required|sometimes',
        'job_title'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $name;

    /**
     * @OA\Property()
     * @var int
     */
    private $contact_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $phone_number;

    /**
     * @OA\Property()
     * @var string
     */
    private $email;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $is_primary;
    /**
     * @OA\Property()
     * @var boolean
     */
    private $job_title;
}
