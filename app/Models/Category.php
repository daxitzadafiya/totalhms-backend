<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Category"))
 */
class Category extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type', 'description', 'added_from', 'added_by', 'company_id', 'industry_id', 'disable_status', 'is_primary'
    ];

    public static $rules = array(
        'name'=>'required',
//        'type'=>'required',
        'added_by'=>'required|numeric|exists:users,id'
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
//        'type'=>'required|sometimes',
        'added_by'=>'required|numeric|exists:users,id|sometimes'
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
    private $added_from;

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

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
     * @var string
     */
    private $industry_id;
}
