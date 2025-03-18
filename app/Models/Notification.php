<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Notification"))
 */
class Notification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'request_push_notification_id', 'read_status', 'show_action'
    ];

    public static $rules = array(
        'user_id'=>'required|numeric|exists:users,id',
        'request_push_notification_id'=>'required|numeric|exists:request_push_notifications,id',
    );

    public static $updateRules = array(
        'user_id'=>'required|numeric|exists:users,id|sometimes',
        'request_push_notification_id'=>'required|numeric|exists:request_push_notifications,id|sometimes',
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
    private $request_push_notification_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $read_status;

    /**
     * @OA\Property()
     * @var integer
     */
    private $show_action;

    public function notification_content() {
        return $this->belongsTo(RequestPushNotification::class, 'request_push_notification_id', 'id');
    }
}
