<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Message"))
 */
class Message extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'messages';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content'
    ];

    public static $rules = array(
        'content'=>'required',
    );

    public static $updateRules = array(
        'content'=>'required|sometimes',
    );

    /**
     * @OA\Property()
     * @var string
     */
    private $content;

}

