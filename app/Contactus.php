<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Contactus extends Model
{
    use SoftDeletes;
    
    protected $primaryKey = 'con_id';
    protected $table = 'contactus';

    const CREATED_AT = "con_created_at";
    const UPDATED_AT = "con_updated_at";
    const DELETED_AT = "con_deleted_at";

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
