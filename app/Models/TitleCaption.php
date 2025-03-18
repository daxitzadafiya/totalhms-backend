<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="TitleCaption"))
 */
class TitleCaption extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'title_caption';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title_key', 'role_id', 'menu', 'sub_menu', 'tab', 'sub_tab', 'note', 'caption', 'activate'
    ];

    public static $rules = array(
        'role_id'=>'required',
        'title_key'=>'required',
        'menu'=>'required',
        'sub_menu'=>'required',
        'tab'=>'required',
        'caption'=>'required',
    );

    public static $updateRules = array(
        'role_id'=>'required|sometimes',
        'title_key'=>'required|sometimes',
        'menu'=>'required|sometimes',
        'sub_menu'=>'required|sometimes',
        'tab'=>'required|sometimes',
        'caption'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $title_key;

    /**
     * @OA\Property()
     * @var int
     */
    private $role_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $menu;

    /**
     * @OA\Property()
     * @var string
     */
    private $sub_menu;

    /**
     * @OA\Property()
     * @var string
     */
    private $tab;

    /**
     * @OA\Property()
     * @var string
     */
    private $sub_tab;

    /**
     * @OA\Property()
     * @var string
     */
    private $note;

    /**
     * @OA\Property()
     * @var string
     */
    private $caption;

    /**
     * @OA\Property()
     * @var boolean
     */
    private $activate;

    public function role() {
        return $this->belongsTo(Role::class);
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
