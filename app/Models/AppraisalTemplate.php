<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(@OA\Xml(name="AppraisalTemplate"))
 */
class AppraisalTemplate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appraisal_templates';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'topic', 'questions', 'company_id'
    ];

    public static $rules = array(
        'topic'=>'required',
        'questions'=>'required',
        'company_id'=>'required|numeric'
    );

    public static $updateRules = array(
        'topic'=>'required|sometimes',
        'questions'=>'required|sometimes',
        'company_id'=>'required|numeric|sometimes'
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $topic;

    /**
     * @OA\Property()
     * @var string
     */
    private $questions;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;
}
