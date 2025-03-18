<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="AttendeeProcessing"))
 */
class ResponsibleProcessing extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'responsible_processing';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by','attendee_id', 'responsible_id', 'comment', 'attachment_id', 'status'
    ];

    public static $rules = array(
        'company_id'=>'numeric|exists:companies,id',
        'added_by'=>'numeric|exists:users,id',
        'attendee_id'=>'numeric|exists:responsible,id',
    );

    public static $updateRules = array(
        'company_id'=>'numeric|exists:companies,id|sometimes',
        'added_by'=>'numeric|exists:users,id|sometimes',
        'attendee_id'=>'numeric|exists:responsible,id|sometimes',
    );
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
    private $attendee_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $status;

    /**
     * @OA\Property()
     * @var int
     */
    private $attachment_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $responsible_attachment_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $comment;

    /**
     * @OA\Property()
     * @var string
     */
    private $responsible_comment;

    public function responsible() {
        return $this->belongsTo(Att::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
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
