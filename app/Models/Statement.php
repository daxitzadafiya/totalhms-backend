<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Absence"))
 */
class Statement extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'statements';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'added_by', 'title', 'description', 'delete_status', 'is_template'
    ];

    public static $rules = array(
        'added_by'=>'required|numeric',
//        'company_id'=>'required|numeric',
        'title'=>'required',
    );

    public static $updateRules = array(
        'added_by'=>'required|numeric|sometimes',
//        'company_id'=>'required|numeric|sometimes',
        'title'=>'required|sometimes',
    );

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
    private $delete_status;

    /**
     * @OA\Property()
     * @var int
     */
    private $company_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $title;

    public function user_added() {
        return $this->belongsTo(User::class, 'added_by');
    }
}
