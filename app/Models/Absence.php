<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Absence"))
 */
class Absence extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'absences';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by', 'absence_reason_id', 'description', 'duration_time', 'processed_by', 'status' ,
        'is_paid', 'start_time', 'end_time', 'project_id', 'illegal', 'absence_reason_id_added_by_admin', 'parent_id',
        'processor', 'reject_reason'
    ];

    public static $rules = array(
        'absence_reason_id'=>'required|numeric',
        'added_by'=>'required|numeric',
        'company_id'=>'required|numeric',
        'duration_time'=>'required|numeric',
    );

    public static $updateRules = array(
        'absence_reason_id'=>'required|numeric|sometimes',
        'added_by'=>'required|numeric|sometimes',
        'company_id'=>'required|numeric|sometimes',
        'duration_time'=>'required|numeric|sometimes',
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $is_paid;

    /**
     * @OA\Property()
     * @var int
     */
    private $processed_by;

    /**
     * @OA\Property()
     * @var int
     */
    private $absence_reason_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $parent_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $absence_reason_id_added_by_admin;

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    /**
     * @OA\Property()
     * @var int
     */
    private $status;

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
    private $project_id;

    /**
     * @OA\Property()
     * @var float
     */
    private $duration_time;

    /**
     * @OA\Property()
     * @var string
     */
    private $start_time;

    /**
     * @OA\Property()
     * @var string
     */
    private $end_time;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $illegal;

    /**
     * @OA\Property()
     * @var string
     */
    private $processor;

    /**
     * @OA\Property()
     * @var string
     */
    private $reject_reason;

    public function user_added() {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function employee() {
        return $this->belongsTo(Employee::class, 'added_by', 'user_id');
    }

    public function user_processed() {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function reason() {
        return $this->belongsTo(AbsenceReason::class, 'absence_reason_id');
    }

    public function attachment() {
        return $this->hasOne(Document::class, 'absence_id', 'id');
    }
}
