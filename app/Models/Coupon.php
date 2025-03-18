<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'discount',
        'code',
        'used_at',
    ];

    public static $rules = array(
        'companyArray'=>'exists:companies,id',
        'name'   => 'required|string',
        'discount'  => 'required|numeric',
        'code'  => 'required|string',
    );

    public function scopeNotUse($query)
    {
        return $query->whereNull('used_at');
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->select('id', 'name');
    }
}