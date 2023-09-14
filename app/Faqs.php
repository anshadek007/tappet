<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Faqs extends Model
{
    use SoftDeletes;
    
    protected $primaryKey = 'faq_id';
    protected $table = 'faq';

    const CREATED_AT = "faq_created_at";
    const UPDATED_AT = "faq_updated_at";
    const DELETED_AT = "faq_deleted_at";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
