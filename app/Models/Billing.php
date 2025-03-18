<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(@OA\Xml(name="Billing"))
 */

class Billing extends Model
{
    /**
     * The table associated with the model.
     *Company Info

     * @var string
     */
    protected $table = 'billings';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'company_id', 'company_name', 'added_by', 'storage_upload', 'storage_repo', 'employee','subscription_id'
    ];

    public static $rules = array(
        'name'=>'required',
    );

    public static $updateRules = array(
        'name'=>'required|sometimes',
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
    private $company_name;

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
    private $employee;

    /**
     * @OA\Property()
     * @var float
     */
    private $storage_upload;

    /**
     * @OA\Property()
     * @var float
     */
    private $storage_repo;

    public function billingDetail()
    {
        return $this->hasOne(BillingDetail::class, 'billing_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }
}
