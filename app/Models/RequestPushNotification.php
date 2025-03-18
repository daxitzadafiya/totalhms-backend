<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="RequestPushNotification"))
 */
class RequestPushNotification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'request_push_notifications';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'message_id', 'send_from', 'send_to', 'url', 'status', 'sending_time', 'type', 'description',
        'send_to_option', 'short_description', 'feature', 'feature_id', 'process_status', 'processed_by'
    ];

    public static $rules = array(
        'company_id'=>'nullable|numeric|exists:companies,id',
        'message_id'=>'numeric|exists:messages,id',
        'send_from'=>'required|numeric|exists:users,id',
    );

    public static $updateRules = array(
        'company_id'=>'nullable|numeric|exists:companies,id|sometimes',
        'message_id'=>'numeric|exists:messages,id|sometimes',
        'send_from'=>'required|numeric|exists:users,id|sometimes',
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
    private $message_id;


    /**
     * @OA\Property()
     * @var int
     */
    private $send_from;


    /**
     * @OA\Property()
     * @var int
     */
    private $status;

    /**
     * @OA\Property()
     * @var string
     */
    private $send_to;

    /**
     * @OA\Property()
     * @var string
     */
    private $url;

    /**
     * @OA\Property()
     * @var string
     */
    private $sending_time;

    /**
     * @OA\Property()
     * @var string
     */
    private $type;

    /**
     * @OA\Property()
     * @var string
     */
    private $short_description;

    /**
     * @OA\Property()
     * @var string
     */
    private $description;

    /**
     * @OA\Property()
     * @var string
     */
    private $send_to_option;

    /**
     * @OA\Property()
     * @var string
     */
    private $feature;

    /**
     * @OA\Property()
     * @var integer
     */
    private $feature_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $process_status;

    /**
     * @OA\Property()
     * @var integer
     */
    private $processed_by;

    public function message() {
        return $this->belongsTo(Message::class);
    }
}
