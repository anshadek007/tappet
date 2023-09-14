<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UserActivities extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'ua_id';
    protected $table = 'user_activities';

    const CREATED_AT = "ua_created_at";
    const UPDATED_AT = "ua_updated_at";
    const DELETED_AT = "ua_deleted_at";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    protected $guarded = [];

}
