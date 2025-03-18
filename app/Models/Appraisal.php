<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Appraisal"))
 */
class Appraisal extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appraisals';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'status', 'topics', 'company_id', 'industry_id'
    ];

    public static $rules = array(
        'user_id'=>'required|numeric',
        'status'=>'required',
        'topics'=>'required',
        'company_id'=>'required|numeric',
        'industry_id'=>'required',
    );

    public static $updateRules = array(
        'user_id'=>'required|numeric|sometimes',
        'status'=>'required|sometimes',
        'topics'=>'required|sometimes',
        'company_id'=>'required|numeric|sometimes',
        'industry_id'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $user_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

    /**
     * @OA\Property()
     * @var string
     */
    private $topics;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $industry_id;

}
