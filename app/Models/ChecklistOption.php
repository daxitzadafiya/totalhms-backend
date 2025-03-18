<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="ChecklistOption"))
 */

class ChecklistOption extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'checklist_options';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type_of_option_answer', 'company_id', 'checklist_id', 'count_option_answers', 'is_template',
        'count_used_time', 'added_by','checklist_required_comment','checklist_required_attachment'
    ];

    public static $rules = array(
        'type_of_option_answer'=>'required|numeric',
        'added_by'=>'required|numeric|exists:users,id'
    );

    public static $updateRules = array(
        'type_of_option_answer'=>'required|numeric|sometimes',
        'added_by'=>'required|numeric|exists:users,id|sometimes'
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
    private $type_of_option_answer;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $checklist_id;

    /**
     * @OA\Property()
     * @var int
     */
    private $count_option_answers;

    /**
     * @OA\Property()
     * @var int
     */
    private $count_used_time;

    /**
     * @OA\Property()
     * @var int
     */
    private $added_by;

    public function optionAnswers()
    {
        return $this->hasMany(ChecklistOptionAnswer::class, 'default_option_id');
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
