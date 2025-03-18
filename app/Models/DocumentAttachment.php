<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(@OA\Xml(name="DocumentAttachment"))
 */
class DocumentAttachment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'documents_attachments';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'document_id', 'original_file_name', 'file_size', 'file_extension',
        'uri'
    ];

    public static $rules = array(
        'document_id'=>'required|numeric'
    );

    public static $updateRules = array(
        'document_id'=>'required|numeric|sometimes'
    );

    public static $fileRules = array(
        'file'=>'required|mimes:jpeg,png,jpg,gif,svg,pdf,csv,xlsx,doc,docx,ppt,pptx,ods,odt,odp,application/csv,application/excel,application/vnd.ms-excel, application/vnd.msexcel,text/csv,text/anytext,text/plain,text/x-c,text/comma-separated-values,inode/x-empty,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    /**
     * @OA\Property()
     * @var int
     */
    private $document_id;

    /**
     * @OA\Property()
     * @var string
     */
    private $original_file_name;

    /**
     * @OA\Property()
     * @var float
     */
    private $file_size;

    /**
     * @OA\Property()
     * @var string
     */
    private $file_extension;

    /**
     * @OA\Property()
     * @var string
     */
    private $uri;

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
