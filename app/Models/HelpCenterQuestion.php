<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="HelpCenterQuestion"))
 */

class HelpCenterQuestion extends Model
{
    /**
     * The table associated with the model.
     *Company Info

     * @var string
     */
    protected $table = 'help_center_questions';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'topic_id', 'title_id', 'question', 'answer', 'only_company_admin', 'disable_status'
    ];

    public static $rules = array(
        'topic_id'=>'required',
    );

    public static $updateRules = array(
        'topic_id'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $question;

    /**
     * @OA\Property()
     * @var string
     */
    private $answer;

    /**
     * @OA\Property()
     * @var int
     */
    private $topic_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $title_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $disable_status;

    public function helpTopic() {
        return $this->belongsTo(HelpCenter::class, 'topic_id');
    }
}
