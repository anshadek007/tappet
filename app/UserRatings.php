<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRatings extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'ur_id';
    protected $table = 'user_ratings';

    const CREATED_AT = "ur_created_at";
    const UPDATED_AT = "ur_updated_at";
    const DELETED_AT = "ur_deleted_at";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    protected $guarded = [];
}
