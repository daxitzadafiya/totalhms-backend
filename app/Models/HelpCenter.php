<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="HelpCenter"))
 */

class HelpCenter extends Model
{
    /**
     * The table associated with the model.
     *Company Info

     * @var string
     */
    protected $table = 'help_center';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'role', 'description', 'type', 'parent_id', 'menu_function', 'only_company_admin', 'disable_status'
    ];

    public static $rules = array(
        'type'=>'required',
    );

    public static $updateRules = array(
        'type'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $name;

    /**
     * @OA\Property()
     * @var string
     */
    private $role;

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
     * @var int
     */
    private $parent_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $menu_function;

    /**
     * @OA\Property()
     * @var int
     */
    private $disable_status;

    public function questions()
    {
        return $this->hasMany(HelpCenterQuestion::class, 'topic_id');
    }
}
