<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="CategoryV2"))
 */
class CategoryV2 extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories_new';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'industry', 'company_id', 'added_by', 'name', 'description', 'type', 'added_from', 'is_priority', 'is_valid', 'source'
    ];

    public static $rules = array(
        'name'=>'required',
        'added_by'=>'numeric|exists:users,id'
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
        'added_by'=>'numeric|exists:users,id|sometimes'
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $industry;

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
     * @var string
     */
    private $type;

    /**
     * @OA\Property()
     * @var string
     */
    private $added_from;

    /**
     * @OA\Property()
     * @var bool
     */
    private $is_priority;

    /**
     * @OA\Property()
     * @var bool
     */
    private $is_valid;

    /**
     * @OA\Property()
     * @var string
     */
    private $source;
}
